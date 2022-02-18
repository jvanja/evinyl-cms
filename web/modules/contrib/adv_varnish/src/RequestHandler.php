<?php

namespace Drupal\adv_varnish;

use Drupal\adv_varnish\Response\ESIResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Class RequestHandler.
 *
 * @package Drupal\adv_varnish
 */
class RequestHandler implements RequestHandlerInterface {

  /**
   * Drupal response.
   *
   * @var \Symfony\Component\HttpFoundation\Response
   */
  protected $response;

  /**
   * Drupal request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * A config factory for retrieving required config objects.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The adv_varnish cookie manager.
   *
   * @var \Drupal\adv_varnish\CookieManagerInterface
   */
  protected $cookieManager;

  /**
   * The adv_varnish cookie manager.
   *
   * @var \Drupal\adv_varnish\CacheManagerInterface
   */
  protected $cacheManager;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * RequestHandler constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Request stack service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\adv_varnish\CookieManagerInterface $cookie_manager
   *   Cookie manager.
   * @param \Drupal\adv_varnish\CacheManagerInterface $cache_manager
   *   Cache manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(RequestStack $request,
                              ConfigFactoryInterface $config_factory,
                              CookieManagerInterface $cookie_manager,
                              CacheManagerInterface $cache_manager,
                              LoggerInterface $logger,
                              StateInterface $state) {
    $this->request = $request->getCurrentRequest();
    $this->config = $config_factory->get('adv_varnish.cache_settings');
    $this->cookieManager = $cookie_manager;
    $this->cacheManager = $cache_manager;
    $this->logger = $logger;
    $this->state = $state;
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
   * Response event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function handleResponseEvent(ResponseEvent $event) {
    $this->response = $event->getResponse();

    // Update cookie.
    $need_reload = $this->cookieManager->cookieUpdate();
    $status = $need_reload ? 'TRUE' : 'FALSE';
    $this->log(RfcLogLevel::DEBUG, 'Cookie update status @status.', ['@status' => $status]);

    // Reload page with updated cookies if needed.
    $needs_update = $need_reload && !($this->response instanceof LocalRedirectResponse);
    if ($needs_update) {
      $this->log(RfcLogLevel::DEBUG, 'Current request needs to be updated. Call for reload method.');
      $this->reload();
    }

    // Get cache settings for the current request.
    $cache_settings = $this->cacheManager->getCacheSettings($event);
    $this->log(RfcLogLevel::DEBUG, 'Cache settings: <pre><code> @cache_settings </code></pre>',
      ['@cache_settings' => print_r($cache_settings, TRUE)]);

    // Set headers.
    $this->setResponseHeaders($cache_settings);
  }

  /**
   * Reload page with updated cookies.
   */
  protected function reload() {
    // Setting cookie will prevent varnish from caching this.
    $this->request->cookies->set('time', time());

    // Get path.
    $path = $this->request->getRequestUri();

    // Create new response.
    $newResponse = new RedirectResponse($path);

    // Send response.
    $newResponse->send();

    return FALSE;
  }

  /**
   * Set varnish specific response headers.
   *
   * @param array $cache_settings
   *   Array of cache settings which will be send with response.
   */
  protected function setResponseHeaders(array $cache_settings) {
    // Get debug settings.
    $debug_mode = $this->config->get('general.debug');

    // Check if we need to enable debug mode.
    if ($debug_mode) {
      $this->response->headers->set(static::HEADER_CACHE_DEBUG, '1');
    }

    $esi_enabled = $this->cacheManager->esiEnabled();

    if ($esi_enabled) {
      $this->response->headers->set(static::HEADER_X_DOESI, TRUE);
    }

    $this->response->headers->set(static::HEADER_GRACE, $cache_settings['grace']);
    if (!$this->response instanceof ESIResponse) {
      $this->response->headers->set(static::HEADER_TTL, $cache_settings['ttl']);
    }
    $this->response->headers->set(static::HEADER_CACHE_TAG, $cache_settings['tags']);
    $this->response->headers->set(static::HEADER_ADV_VARNISH_STATUS, 'Cache-enabled', FALSE);
    $this->response->headers->set(static::HEADER_SECRET_TAG, $this->config->get('general.secret'));
    if ($cache_settings['cache_control']) {
      $this->response->headers->set('Cache-control', $cache_settings['cache_control']);
    }
    $this->response->headers->set('Vary', 'X-Bin');

    // Set Deflate key.
    $deflate_key = $this->state->get('adv_varnish_deflate_key', hash('sha256', static::HEADER_CACHE_TAG));
    $this->response->headers->set(static::HEADER_DEFLATE_KEY, $deflate_key);
  }

}
