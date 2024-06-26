<?php

/**
 * @file
 * Contains \Drupal\evinyl_album\Controller\SearchController.
 */

namespace Drupal\evinyl_search\Controller;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Symfony\Component\HttpFoundation\Response;
use Drupal\image\Entity\ImageStyle;

class SearchController {

  public function buildNodesArray($nodes) {
    $output = ['albums' => [], 'podcasts' => [], 'artists' => []];

    foreach ($nodes as $node) {
      if (property_exists($node, 'podcast_image_id') && $node->podcast_image_id !== NULL) {
        $image_id = $node->podcast_image_id;
      } else if (property_exists($node, 'image_id') && $node->image_id !== NULL) {
        $image_id = $node->image_id;
      } else {
        $image_id = 137; // set default image (fid=137) if one is not found.
      }

      // load the image if image id is of type media otherwise load the file.
      $fid = Media::load($image_id) ? Media::load($image_id)->field_media_image->target_id : $image_id;
      $file = File::load($fid);

      $thumb = ImageStyle::load('thumbnail')->buildUrl($file->uri->value);

      if ($node->type === 'album' || $node->type === 'podcast') {
        $path_base = '/node/';
        $node->type = $node->type . 's'; // make sure all type is plural.
      } else {
        $path_base = '/taxonomy/term/';
      }

      $output[$node->type][] = [
        'name' => $node->title,
        'id' => $node->nid,
        'type' => $node->type,
        'thumb' => $thumb,
        'path' => \Drupal::service('path_alias.manager')->getAliasByPath($path_base . $node->nid),
      ];
    }

    return $output;
  }

  public function content($needle) {
    $database = \Drupal::database();
    $query = $database->query("
      SELECT `node_field_data`.`nid` AS nid,
            `node_field_data`.`title` AS title,
            `node_field_data`.`type` AS type,
            `node__field_image`.`field_image_target_id` AS image_id,
            `node__field_podcast_image`.`field_podcast_image_target_id` AS podcast_image_id
      FROM node_field_data
      LEFT JOIN `node__field_image` ON `node_field_data`.`nid` = `node__field_image`.`entity_id`
      LEFT JOIN `node__field_podcast_image` ON `node_field_data`.`nid` = `node__field_podcast_image`.`entity_id`
      WHERE node_field_data.status = '1' AND
            (node_field_data.type = 'album' OR node_field_data.type = 'podcast') AND
            title LIKE '%{$needle}%'
      UNION
      SELECT `taxonomy_term_field_data`.`tid` AS nid,
            `taxonomy_term_field_data`.`name` as name,
            `taxonomy_term_field_data`.`vid` as type,
            `taxonomy_term__field_user_photo`.`field_user_photo_target_id` as image_id,
            NULL as podcast_image_id
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
      'search' => $needle,
    ];

    $serializer = \Drupal::service('serializer');
    $json_data = $serializer->serialize($return_object, 'json');
    $response = new Response();
    $response->setContent($json_data);
    $response->headers->set('Content-Type', 'application/json');

    return $response;
  }
}
