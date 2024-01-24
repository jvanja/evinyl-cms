<?php
/**
 * @file
 * Contains \Drupal\evinyl_album\Controller\UpgradeAlbumController.
 */
namespace Drupal\evinyl_album\Controller;

// use Symfony\Component\HttpFoundation\JsonResponse;

class UpgradeAlbumController {

  public function update() {
    $export = '';
    $database = \Drupal::database();
    $paras = $database->query('SELECT entity_id, field_album_gallery_photos_target_id, delta FROM {node__field_album_gallery_photos}');
    $parasTarget = $database->query('SELECT * FROM paragraph__field_gallery_image');
    $paras_res = $paras->fetchAll();
    $parasTarget_res = $parasTarget->fetchAll();

    foreach ($paras_res as $para) {
      $entity_id = $para->entity_id;
      $delta = $para->delta;
      $paragraph_id = $para->field_album_gallery_photos_target_id;
      $paragraph = $parasTarget_res[array_search($paragraph_id, array_column($parasTarget_res, 'entity_id'), TRUE)];
      // var_dump($paragraph);
      // die;

      $obj = [
        'bundle' => 'album',
        'deleted' => '0',
        'entity_id' => $entity_id,
        'revision_id' => $entity_id,
        'langcode' => 'und',
        'delta' => $delta,
        'field_images_target_id' => $paragraph->field_gallery_image_target_id,
        'field_images_alt' => $paragraph->field_gallery_image_alt,
        'field_images_title' => $paragraph->field_gallery_image_title,
        'field_images_width' => $paragraph->field_gallery_image_width,
        'field_images_height' => $paragraph->field_gallery_image_height,
      ];
      // $result = $database->insert('node__field_images')->fields($obj)->execute();
      $result = $database->insert('node_revision__field_images')->fields($obj)->execute();
    }

    return [
      '#type' => 'markup',
      '#markup' => t($export . 'All done' . $result),
    ];
  }

}
