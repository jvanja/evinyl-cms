<?php

namespace Drupal\adv_varnish\Controller;

use Drupal\adv_varnish\RequestHandler;
use Drupal\adv_varnish\Response\ESIResponse;
use Drupal\adv_varnish\UserBlocksManagerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class UserBlocksController.
 *
 * @package Drupal\adv_varnish\Controller
 */
class UserBlocksController extends ControllerBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Plugin manager for User blocks plugins.
   *
   * @var \Drupal\adv_varnish\UserBlocksManagerInterface
   */
  protected $pluginManager;

  /**
   * User account interface.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * Constructs a ContactController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\adv_varnish\UserBlocksManagerInterface $plugin_manager
   *   Plugin manager for User blocks plugins.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current account.
   */
  public function __construct(RendererInterface $renderer,
                              UserBlocksManagerInterface $plugin_manager,
                              AccountProxyInterface $account) {
    $this->renderer = $renderer;
    $this->pluginManager = $plugin_manager;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('plugin.manager.user_blocks'),
      $container->get('current_user')
    );
  }

  /**
   * Return rendered block html to replace esi tag.
   */
  public function content() {
    $content = [];
    $response = new ESIResponse();
    $blocks = $this->prepareUserBlocks();

    // Render user block data.
    $av_script = $this->prepareJsData($blocks['js_data']);
    $script = $this->renderer->renderPlain($av_script);
    $html = $this->renderer->renderPlain($blocks['user_blocks']);
    $content[] = $html;
    $content[] = $script;

    $html = implode(PHP_EOL, $content);
    $html = '<div id="ad-varnish-user-blocks" style="display:none;" time="' . time() . '">' . $html . '</div>';

    // Add cache tags for current request.
    $response->getCacheableMetadata()->addCacheTags($blocks['tags']);
    $response->getCacheableMetadata()->addCacheTags([RequestHandler::CACHE_TAG_USER_BLOCKS]);
    $response->getCacheableMetadata()->addCacheTags(['user:' . $this->account->id()]);
    $response->getCacheableMetadata()->addCacheContexts(['user']);

    // Set rendered block as response object content.
    $response->setContent($html);

    return $response;
  }

  /**
   * Prepare Javascript tag for render process.
   *
   * @param array $js_data
   *   Array of js data for processing.
   *
   * @return array
   *   Render array with js data.
   */
  private function prepareJsData(array $js_data) {
    // Prepare user settings which will be
    // merged with drupalSettings on page load.
    $embed_prefix = "\n<!--//--><![CDATA[//><!--\n";
    $embed_suffix = "\n//--><!]]>\n";

    // Defaults for each SCRIPT element.
    $element_defaults = [
      '#type' => 'html_tag',
      '#tag' => 'script',
      '#value' => '',
    ];
    $av_script = $element_defaults;
    $av_script['#value_prefix'] = $embed_prefix;
    $av_script['#value'] = 'var advUserBlocksSettings = ' . Json::encode(NestedArray::mergeDeepArray($js_data)) . ';';
    $av_script['#value_suffix'] = $embed_suffix;

    return $av_script;
  }

  /**
   * Prepare data for user blocks.
   *
   * @return array
   *   Data for user blocks.
   */
  private function prepareUserBlocks() {
    $user_blocks = [];
    $js_data = [];
    $user_data = $this->pluginManager->getUserBlockData();

    // Defaults for each SCRIPT element.
    $element_defaults = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '',
    ];

    // Parse returned data.
    if (isset($user_data['js_settings'])) {
      $js_data[] = $user_data['js_settings'];
    }
    if (isset($user_data['content'])) {
      foreach ($user_data['content'] as $selector => $html) {
        $user_block = $element_defaults;
        $user_block['#value'] = $html;
        $user_block['#attributes'] = [
          'class' => ['ad-varnish-user-block'],
          'data-target' => $selector,
        ];
        $user_blocks[] = $user_block;
      }
    }

    // Set cache tags.
    $tags = $user_data['tags'] ?: [];

    return [
      'js_data' => $js_data,
      'user_blocks' => $user_blocks,
      'tags' => $tags,
    ];
  }

}
