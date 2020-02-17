<?php
/**
 * @file
 * Contains \Drupal\evinyl_album\Controller\AlbumController.
 */
namespace Drupal\evinyl_album\Controller;
// use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Response;
use Drupal\file\Entity\File;

class AlbumController {
  public function content($id = 0) {

    // $albums_nids = \Drupal::entityQuery('node')
    //   ->condition('type', 'album')
    //   ->execute();
    //
    // $albums = Node::loadMultiple($albums_nids);

    // Load entities by their property values.
    // $entities = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'album']);
    $database = \Drupal::database();
    $query = $database->query("
      SELECT `node`.`nid` AS nid,
      `node`.`uuid` AS uuid,
      `node_field_data`.`title` AS title,
      `node__field_image`.`field_image_target_id` AS image_id,
      `node__field_artist_term`.`field_artist_term_target_id` AS artist_id,
      `node__field_genre`.`field_genre_target_id` AS genre_id
      FROM `node`
      INNER JOIN `node_field_data` ON `node`.`nid` = `node_field_data`.`nid`
      INNER JOIN `node__field_image` ON `node`.`nid` = `node__field_image`.`entity_id`
      INNER JOIN `node__field_artist_term` ON `node`.`nid` = `node__field_artist_term`.`entity_id`
      INNER JOIN `node__field_genre` ON `node`.`nid` = `node__field_genre`.`entity_id`
      WHERE node_field_data.status = '1' AND node.type = 'album'
    ");
      // ORDER BY "


    $artists_query = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('artists');
    $genres_query = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('genre');
    $artists = $this->buildTermsArray($artists_query);
    $genres = $this->buildTermsArray($genres_query);

    $entities = $query->fetchAll();
    $albums = $this->buildNodesArray($entities);

    $return_object = [
      'count' => count($albums),
      'albums' => $albums,
      // 'artists' => $artists,
      // 'genres' => $genres,
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
    // $response->setCache($cache_options);
    return $response;
  }

  public function buildTermsArray($terms) {
    $output = [];
    // var_dump($terms);
    foreach($terms as $term) {
      array_push($output, array(
        'tid' => $term->tid,
        'name' => $term->name
      ));
    }
    return $output;
  }

  // `node`.`uuid` AS uuid,
  // `node_field_data`.`title` AS title,
  // `node__field_image`.`field_image_target_id` AS image_id,
  // `node__field_artist_term`.`field_artist_term_target_id` AS artist_id,
  // `node__field_genre`.`field_genre_target_id` AS genre_id,
  public function buildNodesArray($nodes) {
    $output = [];
    $style = \Drupal::entityTypeManager()->getStorage('image_style')->load('thumbnail');
    $artists_ids = [];
    $genres_ids = [];
    foreach($nodes as $node) {
      $file = File::load($node->image_id);
      $thumb = $style->buildUrl($file->uri->value);
      $image_url = $file->uri;
      $cover = ['thumb' => $thumb, 'image' => $file->uri];
      if (!in_array($node->$artist_id, $artists_ids)) {
        array_push($artists_ids, $node->artist_id);
      }
      if (!in_array($node->$genre_id, $genres_ids)) {
        array_push($genres_ids, $node->genre_id);
      }
      array_push($output, array(
        'name' => $node->title,
        'nid' => $node->nid,
        'uuid' => $node->uuid,
        'artist_id' => $artists_ids,
        'genres_id' => $genres_ids,
        'cover' => $cover,
        'path' => \Drupal::service('path.alias_manager')->getAliasByPath('/node/'.$node->nid),
      ));
    }
    return $output;
  }

}
