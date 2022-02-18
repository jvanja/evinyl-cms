<?php

namespace Drupal\adv_varnish;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Provides an interface defining a context manager config page.
 */
interface UserBlocksManagerInterface extends PluginManagerInterface {

  /**
   * Collect user block data from plugins.
   *
   * @return array
   *   User data for block.
   */
  public function getUserBlockData();

}
