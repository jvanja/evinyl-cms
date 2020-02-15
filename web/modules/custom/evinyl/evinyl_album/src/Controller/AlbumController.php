<?php
/**
 * @file
 * Contains \Drupal\evinyl_album\Controller\AlbumController.
 */
namespace Drupal\evinyl_album\Controller;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AlbumController {
  public function content($id = 0) {

    // $albums_nids = \Drupal::entityQuery('node')
    //   ->condition('type', 'album')
    //   ->execute();
    //
    // $albums = Node::loadMultiple($albums_nids);

    // Load entities by their property values.
    $entities = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'album']);

    $return_object = [
      'count' => count($entities),
      'data' => $entities
    ];

    $cache_options = [
      'public' => TRUE,
      'max_age' => '31536000'
    ];

    $serializer = \Drupal::service('serializer');
    $json_data = $serializer->serialize($return_object, 'json');
    $response = new Response();
    $response->setContent($json_data);
    $response->headers->set('Content-Type', 'application/json');
    $response->setCache($cache_options);
    return $response;

    // return array(
    //   '#type' => 'markup',
    //   '#markup' => t('Not neccessary any more. Use core JSON:API'),
    // );
  }
}
