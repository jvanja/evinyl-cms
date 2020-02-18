<?php
/**
 * @file
 * Contains \Drupal\evinyl_album\Controller\CassetteController.
 */
namespace Drupal\evinyl_album\Controller;
// use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Response;
use Drupal\file\Entity\File;

class CassetteController {
  public function content() {

    $database = \Drupal::database();
    $query = $database->query("
      SELECT `node`.`nid` AS nid,
      `node`.`uuid` AS uuid,
      `node_field_data`.`title` AS title,
      `node__field_featured`.`field_featured_value` AS featured,
      `node__field_image`.`field_image_target_id` AS image_id,
      `node__field_artist_term`.`field_artist_term_target_id` AS artist_id,
      `node__field_genre`.`field_genre_target_id` AS genre_id,
      `node__field_likes`.`field_likes_value` AS likes,
      `node__field_is_vinyl`.`field_is_vinyl_value` AS is_vinyl
      FROM `node`
      LEFT JOIN `node__field_likes` ON `node`.`nid` = `node__field_likes`.`entity_id`
      LEFT JOIN `node_field_data` ON `node`.`nid` = `node_field_data`.`nid`
      LEFT JOIN `node__field_featured` ON `node`.`nid` = `node__field_featured`.`entity_id`
      LEFT JOIN `node__field_image` ON `node`.`nid` = `node__field_image`.`entity_id`
      LEFT JOIN `node__field_artist_term` ON `node`.`nid` = `node__field_artist_term`.`entity_id`
      LEFT JOIN `node__field_genre` ON `node`.`nid` = `node__field_genre`.`entity_id`
      LEFT JOIN `node__field_is_vinyl` ON `node`.`nid` = `node__field_is_vinyl`.`entity_id`
      WHERE node_field_data.status = '1' AND node.type = 'album' AND node__field_is_vinyl.field_is_vinyl_value = '1'
    ");

    $entities = $query->fetchAll();
    $albums = $this->buildNodesArray($entities);

    $terms_query = $database->query("
      SELECT
      `taxonomy_term_data`.`tid` AS tid,
      `taxonomy_term_data`.`vid` AS vid,
      `taxonomy_term_data`.`uuid` AS uuid,
      `taxonomy_term_field_data`.`name` AS name,
      `taxonomy_term_field_data`.`description__value` AS description
      FROM `taxonomy_term_data`
      LEFT JOIN `taxonomy_term_field_data` ON `taxonomy_term_data`.`tid` = `taxonomy_term_field_data`.`tid`
      WHERE taxonomy_term_field_data.status = '1'
      ORDER BY taxonomy_term_field_data.name
    ");
    $terms = $terms_query->fetchAll();
    $artists = $this->buildTermsArray($terms, 'artists');
    $genres = $this->buildTermsArray($terms, 'genre');

    // $artists_query = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('artists');
    // $genres_query = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('genre');
    // $artists = $this->buildTermsArray($artists_query);
    // $genres = $this->buildTermsArray($genres_query);

    $return_object = [
      'count' => count($albums),
      'albums' => $albums,
      'artists' => $artists,
      'genres' => $genres,
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

  public function buildTermsArray($terms, $vid) {
    $output = [];
    foreach($terms as $term) {
      if ($term->vid == $vid) {
        array_push($output, array(
          'id' => $term->uuid,
          'tid' => $term->tid,
          'name' => $term->name,
          'path' => \Drupal::service('path.alias_manager')->getAliasByPath('/taxonomy/term/'.$term->tid),
        ));
      }
    }
    return $output;
  }

  public function buildNodesArray($nodes) {
    $output = [];
    $style = \Drupal::entityTypeManager()->getStorage('image_style')->load('thumbnail');
    $artists_ids = [];
    $genres_ids = [];
    $likes_ids = [];
    foreach($nodes as $node) {
      $file = File::load($node->image_id);
      $thumb = $style->buildUrl($file->uri->value);
      $image_url = $file->uri;
      $cover = ['thumb' => $thumb, 'image' => $file->uri];
      $key = array_search($node->nid, array_column($output, 'id'));
      if ($key !== false) {
        if ($node->genre_id && !in_array($node->genre_id, $output[$key]['genres_ids'])) {
          array_push($output[$key]['genres_ids'], (int)$node->genre_id);
        }
        if ($node->artist_id && !in_array($node->artist_id ,$output[$key]['artists_ids'])) {
          array_push($output[$key]['artists_ids'], (int)$node->artist_id);
        }
        if ($node->likes && !in_array($node->likes ,$output[$key]['likes_ids'])) {
          array_push($output[$key]['likes_ids'], $node->likes);
        }
      } else {
        array_push($output, array(
          'name' => $node->title,
          'id' => $node->nid,
          'uuid' => $node->uuid,
          'featured' => $node->featured,
          'likes_ids' => array_unique($likes_ids),
          'artists_ids' => [(int)$node->artist_id],
          'genres_ids' => [(int)$node->genre_id],
          'cover' => $cover,
          'path' => \Drupal::service('path.alias_manager')->getAliasByPath('/node/'.$node->nid),
        ));
      }
    }
    return $output;
  }

}
