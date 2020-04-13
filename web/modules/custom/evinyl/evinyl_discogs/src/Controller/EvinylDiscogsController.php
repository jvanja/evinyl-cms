<?php

namespace Drupal\evinyl_discogs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\node\Entity\Node;
use \Drupal\Core\Link;
use Drupal\taxonomy\Entity\Term;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Exception\ConnectException;

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
    // $this->httpClient = \Drupal::httpClient();

    // $clientFactory = \Drupal::service('http_client_factory');
    // $this->httpClient = $clientFactory->fromOptions(['base_uri' => 'https://api.discogs.com/']);

    $this->httpClient = new Client([
      // Base URI is used with relative requests
      'base_uri' => 'https://api.discogs.com/releases/'
      // 'base_uri' => 'http://httpbin.org'
    ]);

  }

  // /**
  //  * {@inheritdoc}
  //  */
  // public static function create(ContainerInterface $container) {
  //   return new static(
  //     $container->get('http_client')
  //   );
  // }

  /**
   * Posts route callback.
   *
   * @param array $ids
   *   The IDs array
   * @return array
   *   A render array used to show the Posts list.
   */
  public function posts($ids) {

    // CONCURRENTLY
    // http://docs.guzzlephp.org/en/latest/quickstart.html#concurrent-requests
    $promises = [];
    foreach ($ids as $id) {
      $cleanId = trim($id);
      array_push($promises, $this->httpClient->getAsync($cleanId));
    }

    // Wait for the requests to complete; throws a ConnectException
    // if any of the requests fail
    try {
      $responses = Promise\unwrap($promises);
      // $responses = Promise\settle($promises)->wait();
    } catch (ConnectException $e) {
      return false;
    }

    foreach ($responses as $response) {

      if ($response->getStatusCode() != 200) {
        return $build;
      }
      $posts = $response->getBody()->getContents();

      // create albums
      $postObject = json_decode($posts);
      $album = $this->createAlbums($postObject);
    }

    return true;
  }


  protected function createAlbums($albumData) {
    $artistTid = $this->addTaxonomyTerm('artists', $albumData->artists[0]->name);
    $labelTid = $this->addTaxonomyTerm('labels', $albumData->labels[0]->name);
    $node = Node::create([
      'type'              => 'album',
      'status'            => 0,
      'title'             => $albumData->title,
      'field_artist_term' =>  [['target_id' => $artistTid]],
      'field_label'       =>  [['target_id' => $labelTid]],
    ]);
    $node->save();

    $url = $node->toUrl('edit-form');
    $link = Link::fromTextAndUrl($albumData->title, $url);

    return $link->toString();
  }

  /**
   * creates new taxonomy term if it doesnt exist
   * otherwise it returns the existing tid
   *
   * @param string $voc
   *   Vocabulary machine name
   * @param string $term
   *   Term name
   * @return string
   *   Term ID
   */
  protected function addTaxonomyTerm($voc, $term_name) {
    $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $term_name, 'vid' => $voc]);
    $term = reset($term);
    if (empty($term)) {
      $new_term = Term::create([
          'vid' => $voc,
          'name' => $term_name,
      ]);
      $new_term->save();
      return $new_term->id();
    } else {
      return $term->id();
    }
  }


}
