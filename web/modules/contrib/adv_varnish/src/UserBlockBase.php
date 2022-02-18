<?php

namespace Drupal\adv_varnish;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class UserBlockBase.
 *
 * @package Drupal\adv_varnish
 */
class UserBlockBase extends PluginBase implements UserBlocksInterface, ContainerFactoryPluginInterface {

  /**
   * Content of the plugin.
   *
   * @var string
   */
  protected $content;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Returns content for the user block.
   *
   * @return array
   *   Array with user block data.
   */
  public function userBlockData() {
    return [];
  }

}
