<?php

namespace Drupal\adv_varnish;

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\StateInterface;
use Drupal\node\Entity\NodeType;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Class CacheManager.
 */
class CacheManager implements CacheManagerInterface, CacheTagsInvalidatorInterface {

  /**
   * Http method that is used for purging.
   *
   * @var string
   */
  protected $purgeMethod = 'BAN';

  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Paths that is used for purging (should correspond to the VCL file).
   *
   * @var array
   */
  protected $paths = [
    'site' => '/site',
    'tags' => '/tags',
  ];

  /**
   * A config factory for retrieving required config objects.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Drupal request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Drupal response.
   *
   * @var \Symfony\Component\HttpFoundation\Response
   */
  protected $response;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * User account interface.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The route admin context to determine whether a route is an admin one.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Stores the state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * Constructs a new CacheManager object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   Http service for requests.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Request stack service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current account.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher service.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The route admin context to determine whether the route is an admin one.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $killSwitch
   *   The kill switch.
   */
  public function __construct(ClientInterface $http_client,
                              ConfigFactoryInterface $config_factory,
                              RequestStack $request,
                              RouteMatchInterface $route_match,
                              AccountProxyInterface $account,
                              CurrentPathStack $current_path,
                              PathMatcherInterface $path_matcher,
                              AdminContext $admin_context,
                              LoggerInterface $logger,
                              StateInterface $state,
                              KillSwitch $killSwitch) {
    $this->httpClient = $http_client;
    $this->config = $config_factory->get('adv_varnish.cache_settings');
    $this->request = $request->getCurrentRequest();
    $this->routeMatch = $route_match;
    $this->account = $account;
    $this->currentPath = $current_path;
    $this->pathMatcher = $path_matcher;
    $this->adminContext = $admin_context;
    $this->logger = $logger;
    $this->state = $state;
    $this->killSwitch = $killSwitch;
  }

  /**
   * {@inheritdoc}
   */
  public function flushAllCaches() : bool {
    // @TODO: replace X-Varnish-Purge to the const.
    // Options to be sent.
    $options = [
      'headers' => [
        'X-Varnish-Purge' => $this->config->get('general.secret'),
      ],
    ];

    return $this->sendRequest($this->paths['site'], $options);
  }

  /**
   * {@inheritdoc}
   */
  public function purgeTags(array $tags): bool {
    // @TODO: use the const.
    // Options to be sent.
    $options = [
      'headers' => [
        'X-Tag' => self::cacheTagsToHashes($tags),
        'X-Varnish-Purge' => $this->config->get('general.secret'),
      ],
    ];

    return $this->sendRequest($this->paths['tags'], $options);
  }

  /**
   * {@inheritdoc}
   */
  public function purgeUri(string $uri): bool {
    // @TODO: use the const.
    // Options to be sent.
    $options = [
      'headers' => [
        'X-Varnish-Purge' => $this->config->get('general.secret'),
      ],
    ];

    return $this->sendRequest($uri, $options);
  }

  /**
   * Deflate varnish cache for the given value.
   *
   * @param int $number
   *   Value to deflate in varnish.
   *
   * @return bool
   *   Was request successful or not.
   */
  public function deflateCache($number): bool {
    $options = [
      'headers' => [
        'X-Varnish-Purge' => $this->config->get('general.secret'),
        'X-Deflate-Tag' => $number,
        'X-Deflate-Key' => $this->state->get('adv_varnish_deflate_key'),
      ],
    ];

    return $this->sendRequest(static::DEFLATE_PATH, $options);
  }

  /**
   * Helper function to make requests to all servers.
   *
   * @param string $path
   *   Relative path.
   * @param array $options
   *   Options to be sent.
   *
   * @return bool
   *   Result of request.
   */
  private function sendRequest(string $path, array $options) : bool {
    $result = TRUE;

    // If Drupal maintenance_mode and purger_maintenance_mode are active,
    // do not send purge request to Varnish server.
    if ($this->state->get('system.maintenance_mode', FALSE)
    &&  $this->config->get('general.purger_maintenance_mode', FALSE)) {
      $this->log(RfcLogLevel::DEBUG, 'Varnish purge prevented in Maintenance Mode by module settings.');
      return $result;
    }

    // List of servers that should be processed.
    $servers = $this->config->get('general.varnish_server');

    foreach (explode(' ', $servers) as $server) {
      $uri = $server . $path;
      $result = $result && $this->singleRequest($uri, $options);
    }

    return $result;
  }

  /**
   * Send request to the server.
   *
   * @param string $uri
   *   URL of the server.
   * @param array $options
   *   Options that will be sent to the server.
   *
   * @return bool
   *   Result of request.
   */
  protected function singleRequest(string $uri, array $options) : bool {
    $success = FALSE;

    try {
      $response = $this->httpClient->request($this->purgeMethod, $uri, $options);
      $success = $response->getStatusCode() === Response::HTTP_OK;
    }
    catch (GuzzleException $exception) {
      $this->logger->log(RfcLogLevel::ERROR, $exception);
    }

    return $success;
  }

  /**
   * Maps cache tags to hashes.
   *
   * Used when the X-Tag/Surrogate-Key/X-Drupal-Cache-Tags header size otherwise
   * exceeds 16 KB.
   *
   * @param string[] $cache_tags
   *   The cache tags in the header.
   *
   * @return string[]
   *   The hashes to use instead in the header.
   */
  public static function cacheTagsToHashes(array $cache_tags) {
    $hashes = [];
    foreach ($cache_tags as $cache_tag) {
      $hashes[] = substr(md5($cache_tag), 0, 4);
    }
    return implode(' ', $hashes);
  }

  /**
   * Define if esi enabled for this page.
   *
   * @return bool
   *   ESI enable state.
   */
  public function esiEnabled() {
    // Check if ESI globally enabled.
    if (!$this->config->get('available.esi')) {
      return FALSE;
    }

    // Check if we're not on user data route which is already an ESI callback.
    if ($this->isEsiRequest()) {
      return FALSE;
    }

    $this->log(RfcLogLevel::DEBUG, 'ESI enabled for current request.');

    return TRUE;
  }

  /**
   * Define if this is an ESI request.
   *
   * @return bool
   *   ESI enable state.
   */
  public function isEsiRequest() {
    $request_uri = $this->request->getRequestUri();

    // Check if we're not on user data route which is already an ESI callback.
    if (stripos($request_uri, '/adv_varnish/esi/user_blocks') !== FALSE) {
      return TRUE;
    }

    // Check if we're not on user data route which is already an ESI callback.
    if (stripos($request_uri, '/adv_varnish/esi/block') !== FALSE) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Log function to proxy message in case logging is enabled.
   *
   * @param string $severity
   *   Log severity level.
   * @param string $message
   *   Log message.
   * @param array $options
   *   Log message options.
   */
  private function log($severity, $message, array $options = []) {
    $logging = $this->config->get('general.logging');
    if ($logging) {
      $this->logger->log($severity, $message, $options);
    }
  }

  /**
   * Specific entity cache settings getter.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   *
   * @return array
   *   Set of caching settings for current request.
   */
  public function getCacheSettings(ResponseEvent $event) {
    $this->response = $event->getResponse();

    $cache_settings['grace'] = $this->config->get('general.grace');

    // Load $cacheable data from response.
    $cacheable = $this->response->getCacheableMetadata();

    // Define tags.
    $tags = $cacheable->getCacheTags();
    $cache_settings['tags'] = self::cacheTagsToHashes($tags);

    // Set TTL for the request.
    $ttl = $this->config->get('general.page_cache_maximum_age');

    // Override TTL for node entities.
    $entity = $this->routeMatch->getParameters()->get('node');
    if ($entity) {

      // Get TTL from configuration of node bundle and check that we need to
      // override default page cache maximum age value.
      $node_type = NodeType::load($entity->bundle());
      if ($node_type) {
        $ttl = $node_type->getThirdPartySetting('adv_varnish', 'override') ? $node_type->getThirdPartySetting('adv_varnish', 'ttl') : $ttl;
      }
    }

    $cache_settings['ttl'] = $ttl;

    // Get cache control header.
    $authenticated = $this->account->isAuthenticated();
    $key = 'cache_control.' . ($authenticated ? 'authenticated' : 'anonymous');
    $cache_control_config = $this->config->get($key);
    $rules = explode(PHP_EOL, $cache_control_config);
    $cache_control = '';
    $path = $this->currentPath->getPath();
    foreach ($rules as $line) {
      $rule = explode('|', trim($line));
      if ($this->pathMatcher->matchPath($path, $rule[0])) {
        $cache_control = $rule[1];

        // First one wins.
        break;
      }
    }
    $cache_settings['cache_control'] = $cache_control;

    return $cache_settings;
  }

  /**
   * Define if caching enabled for the page and we can proceed with the request.
   *
   * @return bool
   *   Result of varnish enable state.
   */
  public function cachingEnabled() {

    // Check if user has permission to bypass varnish.
    if ($this->account->hasPermission('bypass advanced varnish cache')) {
      $this->log(RfcLogLevel::DEBUG, 'Cache disabled by Bypass Varnish permission.');
      return FALSE;
    }

    // First check if Varnish cache is entirely disabled for the site.
    if (!$this->config->get('available.enable_cache')) {
      $this->log(RfcLogLevel::DEBUG, 'Cache disabled by module settings.');
      return FALSE;
    }

    // Check if we acn be on disabled domain.
    $excluded_urls = explode(PHP_EOL, $this->config->get('available.excluded_urls'));
    $request_uri = $this->request->getRequestUri();
    $host = $this->request->getHost();
    foreach ($excluded_urls as $line) {
      $rule = explode('|', trim($line));
      if ((($rule[0] === '*') || ($host === $rule[0]))
        && (($rule[1] === '*') || strpos($request_uri, $rule[1]) === 0)) {
        $this->log(RfcLogLevel::DEBUG, 'Cache disabled by excluded url rule @rule', ['@rule' => $rule[1]]);
        return FALSE;
      }
    }

    // Check if user is authenticated and we can use cache for such users.
    $authenticated = $this->account->isAuthenticated();
    $cache_authenticated = $this->config->get('available.authenticated_users');
    if ($authenticated && !$cache_authenticated) {
      $this->log(RfcLogLevel::DEBUG, 'Cache disabled for authenticated users.');
      return FALSE;
    }

    // Disable cache if page_cache_kill_switch is called.
    if ($this->killSwitch->check(new Response(), $this->request) == 'deny') {
      $this->log(RfcLogLevel::DEBUG, 'Cache disabled - page_kill_switch.');
      return FALSE;
    }

    // Disable cache if a route's option _no_cache is TRUE.
    if ((strpos($request_uri, '/adv_varnish/esi/') === FALSE) &&
      ($route = $this->routeMatch->getRouteObject()) && $route->getOption('no_cache')) {
      $this->log(RfcLogLevel::DEBUG, 'Cache disabled - route has option _no_cache.');
      return FALSE;
    }

    // Disable varnish for admin pages.
    $admin_route = $this->adminContext->isAdminRoute($this->routeMatch->getRouteObject());
    if ($admin_route) {
      $this->log(RfcLogLevel::DEBUG, 'Cache disabled for admin theme.');
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {

    // Check if need to invalidate tags by built in purger.
    $purger = $this->config->get('general.varnish_purger');
    if (!$purger) {
      return;
    }

    // Check if tags actually strings.
    assert(Inspector::assertAllStrings($tags), 'Cache tags must be strings.');
    $this->log(RfcLogLevel::DEBUG, 'Tags for invalidation: ' . implode(' ', $tags));

    // Invalidate tags.
    $this->purgeTags($tags);
  }

  /**
   * Purge user blocks if required.
   */
  public function purgeUserBlocks() {
    $esi = $this->config->get('available.esi');
    $purge_user_blocks = $this->config->get('available.esi_purge_user_blocks');
    if ($esi && $purge_user_blocks && $this->request->isMethod('POST') && $this->account->id() > 0) {
      $this->invalidateTags(['user:' . $this->account->id()]);
    }
  }

}
