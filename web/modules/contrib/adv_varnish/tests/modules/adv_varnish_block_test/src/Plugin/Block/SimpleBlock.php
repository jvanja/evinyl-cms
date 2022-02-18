<?php

namespace Drupal\adv_varnish_block_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a simple block.
 *
 * @Block(
 *   id = "simple_block",
 *   admin_label = @Translation("Simple block")
 * )
 */
class SimpleBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => 'Simple block',
    ];
  }

}
