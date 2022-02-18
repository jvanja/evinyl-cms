<?php

namespace Drupal\adv_varnish\Controller;

use Drupal\adv_varnish\RequestHandler;
use Drupal\adv_varnish\Response\ESIResponse;
use Drupal\block\BlockInterface;
use Drupal\block\Entity\Block;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ESIBlockController.
 *
 * @package Drupal\adv_varnish\Controller
 */
class ESIBlockController extends ControllerBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ESIBlockController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(RendererInterface $renderer,
                              EntityTypeManagerInterface $entity_type_manager) {
    $this->renderer = $renderer;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Return rendered block html to replace esi tag.
   *
   * @param string $block_id
   *   The block ID.
   *
   * @return \Drupal\adv_varnish\Response\ESIResponse
   *   ESI response with block content.
   */
  public function content($block_id) {
    $response = new ESIResponse();

    $block = Block::load($block_id);
    if (!$block instanceof BlockInterface) {
      return $response;
    }

    // Render block.
    $response->setEntity($block);

    // Add block to dependency to respect block tags.
    $response->addCacheableDependency($block);

    // Check if block has special plugin and add it to dependency.
    $plugin = $block->getPlugin();
    if (is_object($plugin)) {
      $response->addCacheableDependency($plugin);
    }

    $build = $this->entityTypeManager->getViewBuilder('block')
      ->view($block);
    $content = $this->renderer->renderPlain($build);

    // Set rendered block as response object content.
    $response->setContent($content);

    $settings = $block->get('settings');
    $response->headers->set(RequestHandler::HEADER_TTL, $settings['cache']['ttl'], TRUE);

    // Depends on type set the cache context.
    $type = $settings['cache']['cachemode'];
    if ((int)$type === RequestHandler::CACHE_PER_ROLE) {
      $response->getCacheableMetadata()->addCacheContexts(['user.roles']);
    }
    elseif ((int)$type === RequestHandler::CACHE_PER_USER) {
      $response->getCacheableMetadata()->addCacheContexts(['user']);
    }

    return $response;
  }

}
