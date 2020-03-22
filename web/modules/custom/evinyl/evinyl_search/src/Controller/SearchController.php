<?php
/**
 * @file
 * Contains \Drupal\evinyl_album\Controller\SearchController.
 */
namespace Drupal\evinyl_search\Controller;

use Symfony\Component\HttpFoundation\Response;

class SearchController {
  public function content($needle) {
    $database = \Drupal::database();
    $query = $database->query("
      SELECT `node_field_data`.`nid` AS nid,
            `node_field_data`.`title` AS title,
            `node_field_data`.`type` AS type
      FROM node_field_data
      WHERE node_field_data.status = '1' AND
            node_field_data.type = 'album' AND
            title LIKE '%{$needle}%'
      UNION
      SELECT `taxonomy_term_field_data`.`tid` AS nid,
      `taxonomy_term_field_data`.`name` AS name,
      `taxonomy_term_field_data`.`vid` AS type
      FROM taxonomy_term_field_data
      WHERE taxonomy_term_field_data.status = '1' AND
            taxonomy_term_field_data.vid = 'artists' AND
            name LIKE '%{$needle}%'
    ");
    $entities = $query->fetchAll();
    $albums = $this->buildNodesArray($entities);

    $return_object = [
      'albums' => $albums,
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
    $output = [];
    foreach($nodes as $node) {
      if ($node->type == 'album') {
        $path_base = '/node/';
      } else {
        $path_base = '/taxonomy/term/';
      }
      array_push($output, array(
        'name' => $node->title,
        'id' => $node->nid,
        'type' => $node->type,
        'path' => \Drupal::service('path.alias_manager')->getAliasByPath($path_base . $node->nid),
      ));
    }
    return $output;
  }
}
