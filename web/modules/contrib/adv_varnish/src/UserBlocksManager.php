<?php

namespace Drupal\adv_varnish;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * UserBlock entity manager.
 */
class UserBlocksManager extends DefaultPluginManager implements UserBlocksManagerInterface {

  /**
   * Constructs an ConfigPagesContextManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/UserBlocks', $namespaces, $module_handler, '\Drupal\adv_varnish\UserBlocksInterface', 'Drupal\adv_varnish\Annotation\UserBlocks');

    $this->alterInfo('adv_varnish_user_blocks_info');
    $this->setCacheBackend($cache_backend, 'adv_varnish_user_blocks');
  }

  /**
   * Collect user block data from plugins.
   *
   * @return array
   *   User data for block.
   */
  public function getUserBlockData() {
    $user_data = [];
    $content = [];
    $js_settings = [];
    $tags = [];

    // Call for plugins to retrieve user blocks data.
    $plugins = $this->getDefinitions();
    foreach ($plugins as $plugin) {
      $plugin_instance = $this->createInstance($plugin['id'], []);
      $plugin_result = $plugin_instance->userBlockData();
      if (is_array($plugin_result)) {
        $content[] = $plugin_result['content'];
        $js_settings[] = $plugin_result['js_settings'];
        $tags[] = $plugin_result['tags'];
      }
    }

    $user_data['content'] = !empty($content) ? array_merge(...$content) : [];
    $user_data['js_settings'] = !empty($js_settings) ? array_merge(...$js_settings) : [];
    $user_data['tags'] = !empty($tags) ? array_merge(...$tags) : [];

    return $user_data;
  }

}
