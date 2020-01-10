<?php
/**
 * @file
 * Contains \Drupal\evinyl_album\Controller\IncrementTrackPlayController.
 */
namespace Drupal\evinyl_album\Controller;
class IncrementTrackPlayController {
  public function content($id = 0) {
    return array(
      '#type' => 'markup',
      '#markup' => t('Hello, World! ' . $id),
    );
  }
}
