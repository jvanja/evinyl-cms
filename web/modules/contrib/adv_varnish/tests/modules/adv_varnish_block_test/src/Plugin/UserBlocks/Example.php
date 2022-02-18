<?php

namespace Drupal\adv_varnish_block_test\Plugin\UserBlocks;

use Drupal\adv_varnish\UserBlockBase;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a language config pages context.
 *
 * @UserBlocks(
 *   id = "example",
 *   label = @Translation("Example"),
 * )
 */
class Example extends UserBlockBase {

  /**
   * User account interface.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration = $configuration;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * User block data for ESI request.
   *
   * @return array
   *   Content for user data block.
   */
  public function userBlockData() {
    $block = [];

    $block['js_settings'] = [
      'user_info' => [
        'user' => [
          'name' => $this->account->getDisplayName(),
          'id' => $this->account->id(),
        ],
      ],
    ];

    $block['content'] = [
      '#block-bartik-account-menu ul .menu-item:first a' => t('Hi, @user', [
        '@user' => $this->account->getDisplayName(),
      ]),
    ];
    $block['tags'] = ['user:' . $this->account->id()];

    return $block;
  }

}
