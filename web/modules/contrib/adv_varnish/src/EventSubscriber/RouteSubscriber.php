<?php

namespace Drupal\adv_varnish\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines dynamic routes for Advanced Varnish.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The Config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory interface service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('adv_varnish.cache_settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = [];

    // If purger is disabled no sense to show this routes to user.
    $purger_enabled = $this->config->get('general.varnish_purger');
    if (!$purger_enabled) {
      $routes['adv_varnish.clear_cache_form'] = new Route(
        '/admin/config/development/adv_varnish/clear_cache',
        [],
        ['_access' => 'FALSE']
      );
      $routes['adv_varnish.deflate_form'] = new Route(
        '/admin/config/development/adv_varnish/clear_cache',
        [],
        ['_access' => 'FALSE']
      );
      return $routes;
    }

    $routes['adv_varnish.clear_cache_form'] = new Route(
      '/admin/config/development/adv_varnish/clear_cache',
      [
        '_form' => '\Drupal\adv_varnish\Form\ClearCacheForm',
        '_title' => 'Clear Varnish cache',
      ],
      [
        '_permission'  => 'administer advanced varnish configuration',
      ],
      [
        '_admin_route' => TRUE,
      ]
    );

    $routes['adv_varnish.deflate_form'] = new Route(
      '/admin/config/development/adv_varnish/deflate',
      [
        '_form' => '\Drupal\adv_varnish\Form\DeflateForm',
        '_title' => 'Deflate Varnish cache',
      ],
      [
        '_permission'  => 'administer advanced varnish configuration',
      ],
      [
        '_admin_route' => TRUE,
      ]
    );

    return $routes;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

  }

}
