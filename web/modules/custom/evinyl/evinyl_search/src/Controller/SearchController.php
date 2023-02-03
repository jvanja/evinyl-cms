<?php
/**
 * @file
 * Contains \Drupal\evinyl_album\Controller\SearchController.
 */
namespace Drupal\evinyl_search\Controller;

use Symfony\Component\HttpFoundation\Response;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;

class SearchController {
  public function content($needle) {
    $database = \Drupal::database();
    $query = $database->query("
      SELECT `node_field_data`.`nid` AS nid,
            `node_field_data`.`title` AS title,
            `node_field_data`.`type` AS type,
            `node__field_image`.`field_image_target_id` AS image_id
      FROM node_field_data
      LEFT JOIN `node__field_image` ON `node_field_data`.`nid` = `node__field_image`.`entity_id`
      WHERE node_field_data.status = '1' AND
            node_field_data.type = 'album' AND
            title LIKE '%{$needle}%'
      UNION
      SELECT `taxonomy_term_field_data`.`tid` AS nid,
      `taxonomy_term_field_data`.`name` AS name,
      `taxonomy_term_field_data`.`vid` AS type,
      `taxonomy_term__field_user_photo`.`field_user_photo_target_id` AS image_id
      FROM taxonomy_term_field_data
      LEFT JOIN `taxonomy_term__field_user_photo` ON `taxonomy_term_field_data`.`tid` = `taxonomy_term__field_user_photo`.`entity_id`
      WHERE taxonomy_term_field_data.status = '1' AND
            taxonomy_term_field_data.vid = 'artists' AND
            name LIKE '%{$needle}%'
    ");
    $entities = $query->fetchAll();
    $results = $this->buildNodesArray($entities);

    $return_object = [
      'results' => $results,
      'search' => $needle
    ];

    $serializer = \Drupal::service('serializer');
    $json_data = $serializer->serialize($return_object, 'json');
    $response = new Response();
    $response->setContent($json_data);
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

  public function buildNodesArray($nodes) {
    $output = ['albums' => [], 'artists' => []];
    $thumb_style = \Drupal::entityTypeManager()->getStorage('image_style')->load('thumbnail');
    foreach($nodes as $node) {
      // set default image (fid=137) if one is not found.
      $node->image_id = $node->image_id == null ? 137 : $node->image_id;
      // $file = File::load($node->image_id);

      $media = Media::load($node->image_id);
      if ($media) {
        $fid = $media->field_media_image->target_id;
        $file = File::load($fid);
      } else {
        $file = File::load($node->image_id);
      }

      $thumb = $thumb_style->buildUrl($file->uri->value);

      if ($node->type == 'album') {
        $path_base = '/node/';
        array_push($output['albums'], array(
          'name' => $node->title,
          'id' => $node->nid,
          'type' => $node->type,
          'thumb' => $thumb,
          'path' => \Drupal::service('path_alias.manager')->getAliasByPath($path_base . $node->nid),
        ));
      } else {
        $path_base = '/taxonomy/term/';
        array_push($output['artists'], array(
          'name' => $node->title,
          'id' => $node->nid,
          'type' => $node->type,
          'thumb' => $thumb,
          'path' => \Drupal::service('path_alias.manager')->getAliasByPath($path_base . $node->nid),
        ));
      }
    }
    return $output;
  }
}
