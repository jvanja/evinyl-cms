<?php

namespace Drupal\adv_varnish;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\StreamWrapper\PublicStream;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class CookieManager.
 *
 * @package Drupal\adv_varnish
 */
class CookieManager implements CookieManagerInterface {

  /**
   * The state key/value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Drupal request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * User account interface.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * A config factory for retrieving required config objects.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The file handler under test.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * RequestHandler constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key/value store.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Request stack service.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current account.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file handler.
   */
  public function __construct(StateInterface $state,
                              RequestStack $request,
                              AccountProxyInterface $account,
                              TimeInterface $time,
                              ConfigFactoryInterface $config_factory,
                              LoggerInterface $logger,
                              FileSystemInterface $file_system) {
    $this->state = $state;
    $this->request = $request->getCurrentRequest();
    $this->account = $account;
    $this->time = $time;
    $this->config = $config_factory->get('adv_varnish.cache_settings');
    $this->logger = $logger;
    $this->fileSystem = $file_system;
  }

  /**
   * Updates AdvVarnish user cookie if required.
   *
   * @return bool
   *   Defines if page needs to be reloaded after cookie is updated.
   */
  public function cookieUpdate() {
    $reload = FALSE;

    // Cookies may be disabled for resource files,
    // so no need to redirect in such a case.
    if ($this->redirectForbidden()) {
      $this->log(RfcLogLevel::DEBUG, 'Redirect forbidden. Cookie would not be updated.');
      return FALSE;
    }

    $cookie = $this->getCurrentBin();
    $this->log(RfcLogLevel::DEBUG, 'Generated cookie info:<pre><code> @cookie </code></pre>', [
      '@cookie' => print_r($cookie, TRUE),
    ]);

    // Update cookies if did not match.
    $old_cookie_bin = $_COOKIE[static::COOKIE_BIN] ?? '';
    if ($old_cookie_bin !== $cookie['bin']) {
      $this->log(RfcLogLevel::DEBUG, 'Diff detected between generated and User cookies. User cookie: @cookie', [
        '@cookie' => empty($old_cookie_bin) ? 'ADVBIN cookie is empty' : $_COOKIE[static::COOKIE_BIN],
      ]);

      // Update cookies.
      $params = session_get_cookie_params();
      $expire = $params['lifetime'] ? ($this->time->getRequestTime() + $params['lifetime']) : 0;
      setcookie(static::COOKIE_BIN, $cookie['bin'], $expire, $params['path'], $params['domain'], FALSE, $params['httponly']);
      setcookie(static::COOKIE_INF, $cookie['inf'], $expire, $params['path'], $params['domain'], FALSE, $params['httponly']);
      $this->log(RfcLogLevel::DEBUG, 'Cookie updated, page marked to be reloaded.');

      // Reload the page to apply new cookie.
      $reload = TRUE;
    }

    return $reload;
  }

  /**
   * Define which bin should be used for current request.
   *
   * @return array
   *   Cookies for current request.
   */
  private function getCurrentBin() {
    if ($this->account->hasPermission('bypass advanced varnish cache')) {
      $cookie_inf = 'bypass_varnish';
      $cookie_bin = hash('sha256', $cookie_inf);
      $this->log(RfcLogLevel::DEBUG, 'User has Varnish bypass permission. Mark bin as bypass one.');
    }
    elseif ($this->account->id() > 0) {
      $this->log(RfcLogLevel::DEBUG, 'Starting to prepare bin for registered user.');
      $roles = $this->account->getRoles();
      sort($roles);
      $bin = implode('__', $roles);

      // Set cookie inf.
      $cookie_inf = $bin;
      $this->log(RfcLogLevel::DEBUG, 'Un-hashed bin is: @bin.', ['@bin' => $bin]);

      // Hash bin (PER_ROLE-PER_PAGE).
      $cookie_bin = hash('sha256', $bin);

      // Get hashing noise and hash bin value additionally.
      $noise = $this->config->get('general.noise');
      if (!empty($noise)) {
        $cookie_bin = hash('sha256', $cookie_bin . $noise);
      }
    }
    else {
      $this->log(RfcLogLevel::DEBUG, 'User is anonymous, bin set to anonymous.');

      // Bin for anon user.
      $cookie_inf = $cookie_bin = '';
    }

    return ['bin' => $cookie_bin, 'inf' => $cookie_inf];
  }

  /**
   * Check if redirect enabled.
   *
   * Check if this page is allowed to redirect,
   * be default resource files should not be redirected.
   *
   * @return bool
   *   Defines if page needs to be reloaded.
   */
  public function redirectForbidden() {

    $redirect_forbidden = $this->state->get('adv_varnish__redirect_forbidden', FALSE);
    $redirect_forbidden_nocookie = $this->state->get('adv_varnish__redirect_forbidden_nocookie', TRUE);
    if (!empty($_SESSION['adv_varnish__redirect_forbidden']) || $redirect_forbidden) {
      return TRUE;
    }
    elseif ($redirect_forbidden_nocookie && empty($_COOKIE)) {
      // This one is important as search engines don't have cookie support
      // and we don't want them to enter infinite loop.
      // Also images may have their cookies be stripped at Varnish level.
      return TRUE;
    }

    // Get current path as default.
    $current_path = $this->request->getRequestUri();

    // By default exclude resource path.
    $path_to_exclude = [
      PublicStream::basePath(),
      PrivateStream::basePath(),
      $this->fileSystem->getTempDirectory(),
    ];
    $path_to_exclude = array_filter($path_to_exclude, 'trim');

    // Check against excluded path.
    $forbidden = FALSE;
    foreach ($path_to_exclude as $exclude) {
      if (strpos($current_path, $exclude) === 0) {
        $this->log(RfcLogLevel::DEBUG, 'Redirect forbidden by path: @path.', ['@path' => $exclude]);
        $forbidden = TRUE;
      }
    }

    return $forbidden;
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

}
