<?php
/**
 * @file
 * Contains \Drupal\evinyl_album\Controller\CassetteController.
 */
namespace Drupal\evinyl_album\Controller;
// use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Response;
use Drupal\file\Entity\File;

class CassetteController extends AlbumController {
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

}
