<?php

namespace Drupal\evinyl_discogs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;

/**
 * Class MyController.
 *
 * @package Drupal\evinyl_discogs\Controller
 */
class DiscogsController extends ControllerBase {

  /**
   * Guzzle\Client instance.
   *
   * @var \Guzzle\Client
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(Client $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }

  /**
   * Posts route callback.
   *
   * @param array $ids
   *   The IDs array
   * @return array
   *   A render array used to show the Posts list.
   */
  public function posts($ids) {
    $build = [
      '#theme' => 'mymodule_posts_list',
      '#posts' => [],
    ];

    $apiBaseUrl = 'https://api.discogs.com/releases/249504';

    // $client = \Drupal::httpClient();
    // $client->request('GET', $apiBaseUrl);
    // CONCURRENTLY
    // http://docs.guzzlephp.org/en/latest/quickstart.html#concurrent-requests

    $request = $this->httpClient->request('GET', $apiBaseUrl);

    if ($request->getStatusCode() != 200) {
      return $build;
    }

    $posts = $request->getBody()->getContents();
    foreach ($posts as $post) {
      $build['#posts'][] = [
        'id' => $post['id'],
        'title' => $post['title'],
        'text' => $post['text'],
      ];
    }
    return $build;
  }

}
