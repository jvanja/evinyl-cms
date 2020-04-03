<?php

namespace Drupal\evinyl_discogs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;
use \Drupal\node\Entity\Node;
use \Drupal\Core\Link;

/**
 * Class MyController.
 *
 * @package Drupal\evinyl_discogs\Controller
 */
class EvinylDiscogsController extends ControllerBase {

  /**
   * Guzzle\Client instance.
   *
   * @var \Guzzle\Client
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->httpClient = \Drupal::httpClient();
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

    $apiBaseUrl = 'https://api.discogs.com/releases/249504';

    // CONCURRENTLY
    // http://docs.guzzlephp.org/en/latest/quickstart.html#concurrent-requests

    $request = $this->httpClient->request('GET', $apiBaseUrl);

    if ($request->getStatusCode() != 200) {
      return $build;
    }

    $posts = $request->getBody()->getContents();

    // create albums
    $postObject = json_decode($posts);
    $album = $this->createAlbums($postObject);


    // foreach ($posts as $post) {
    //   $build['#posts'][] = [
    //     'id' => $post['id'],
    //     'title' => $post['title'],
    //     'text' => $post['text'],
    //   ];
    // }

    return $album;
  }


  protected function createAlbums($albumData) {
    $node = Node::create([
      'type'        => 'album',
      'status'      => 0,
      'title'       => $albumData->title,
    ]);
    $node->save();

    $url = $node->toUrl('edit-form');
    $link = Link::fromTextAndUrl($albumData->title, $url);

    return $link->toString();
  }
}
