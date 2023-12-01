<?php
/**
 * @file
 * Contains \Drupal\evinyl_album\Controller\ArtistsController.
 */
namespace Drupal\evinyl_album\Controller;

// use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\Response;

// use Drupal\paragraphs\Entity\Paragraph;

class ArtistsController {

  public function buildArtistsArray($terms, $vid) {
    $output = [];
    $thumb_style = \Drupal::entityTypeManager()->getStorage('image_style')->load('thumbnail');
    $medium_style = \Drupal::entityTypeManager()->getStorage('image_style')->load('medium');

    foreach ($terms as $term) {
      if ($term->vid === $vid) {
        $term->image_id = $term->image_id === NULL ? 137 : $term->image_id;
        $file = File::load($term->image_id);
        $thumb = $thumb_style->buildUrl($file->uri->value);
        $medium = $medium_style->buildUrl($file->uri->value);
        $image_url = $file->uri;
        $cover = ['thumb' => $thumb, 'medium' => $medium, 'image' => $image_url];
        $output[] = [
          'id' => $term->uuid,
          'tid' => $term->tid,
          'name' => $term->name,
          'path' => \Drupal::service('path_alias.manager')->getAliasByPath('/taxonomy/term/' . $term->tid),
          'band_members' => $term->band_members,
          'cover' => $cover,
        ];
      }
    }

    return $output;
  }

  public function buildGenresArray($terms, $vid) {
    $output = [];

    foreach ($terms as $term) {
      if ($term->vid === $vid) {
        $output[] = [
          'id' => $term->uuid,
          'tid' => $term->tid,
          'name' => $term->name,
          'path' => \Drupal::service('path_alias.manager')->getAliasByPath('/taxonomy/term/' . $term->tid),
        ];
      }
    }

    return $output;
  }

  public function content() {
    $database = \Drupal::database();

    $terms_query = $database->query("
      SELECT
      `taxonomy_term_data`.`tid` AS tid,
      `taxonomy_term_data`.`vid` AS vid,
      `taxonomy_term_data`.`uuid` AS uuid,
      `taxonomy_term_field_data`.`name` AS name,
      `taxonomy_term_field_data`.`description__value` AS description,
      `taxonomy_term__field_user_photo`.`field_user_photo_target_id` AS image_id,
      `taxonomy_term__field_band_members`.`field_band_members_value` AS band_members
      FROM `taxonomy_term_data`
      LEFT JOIN `taxonomy_term_field_data` ON `taxonomy_term_data`.`tid` = `taxonomy_term_field_data`.`tid`
      LEFT JOIN `taxonomy_term__field_user_photo` ON `taxonomy_term_data`.`tid` = `taxonomy_term__field_user_photo`.`entity_id`
      LEFT JOIN `taxonomy_term__field_band_members` ON `taxonomy_term_data`.`tid` = `taxonomy_term__field_band_members`.`entity_id`
      WHERE taxonomy_term_field_data.status = '1'
      ORDER BY taxonomy_term_field_data.name
    ");
    $terms = $terms_query->fetchAll();
    $artists = $this->buildArtistsArray($terms, 'artists');
    $genres = $this->buildGenresArray($terms, 'genre');

    $return_object = [
      'count' => \count($artists),
      'artists' => $artists,
      'genres' => $genres,
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
