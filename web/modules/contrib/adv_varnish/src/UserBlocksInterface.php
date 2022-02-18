<?php

namespace Drupal\adv_varnish;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface UserBlocksInterface.
 *
 * @package Drupal\adv_varnish
 */
interface UserBlocksInterface extends PluginInspectionInterface {

  /**
   * User block data for ESI request.
   *
   * @return array
   *   Content for user data block.
   */
  public function userBlockData();

}
