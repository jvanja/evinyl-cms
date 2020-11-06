<?php
/**
 * @file
 * Contains \Drupal\evinyl_album\Controller\AlbumController.
 */
namespace Drupal\evinyl_album\Controller;
// use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Response;
use Drupal\file\Entity\File;
use Drupal\paragraphs\Entity\Paragraph;

class AlbumController {
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
      WHERE node_field_data.status = '1' AND node.type = 'album' AND node__field_is_vinyl.field_is_vinyl_value = '0'
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
          'path' => \Drupal::service('path_alias.manager')->getAliasByPath('/taxonomy/term/'.$term->tid),
        ));
      }
    }
    return $output;
  }

  public function buildNodesArray($nodes) {
    global $base_url;
    $root_path = str_replace('index.php', '', $_SERVER['SCRIPT_NAME']);

    $output = [];
    $thumb_style = \Drupal::entityTypeManager()->getStorage('image_style')->load('thumbnail');
    $medium_style = \Drupal::entityTypeManager()->getStorage('image_style')->load('medium');
    $artists_ids = [];
    $genres_ids = [];
    $likes_ids = [];
    foreach($nodes as $node) {
      // set default image (fid=137) if one is not found.
      $node->image_id = $node->image_id == null ? 137 : $node->image_id;
      $file = File::load($node->image_id);
      $thumb = $thumb_style->buildUrl($file->uri->value);
      $medium = $medium_style->buildUrl($file->uri->value);
      $image_url = $file->uri;
      $cover = ['thumb' => $thumb, 'medium' => $medium, 'image' => $image_url];
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
          'path' => \Drupal::service('path_alias.manager')->getAliasByPath('/node/'.$node->nid),
        ));
      }
    }
    return $output;
  }

  public function update() {

    $export = '';
    $database = \Drupal::database();
    $paras = $database->query("SELECT entity_id, field_image_target_id FROM {paragraph__field_image}");
    $paras_res = $paras->fetchAll();
    $targets = implode(',' ,array_column($paras_res, 'field_image_target_id'));

    foreach ($paras_res as $para) {
      $paragraph_id = $para->entity_id;
      $paragraph = Paragraph::load($paragraph_id);
      $paragraph->set('field_gallery_image',  $para->field_image_target_id);
      $paragraph->save();
    }
    return array(
      '#type' => 'markup',
      '#markup' => t($export . 'All done'),
    );
  }
}
