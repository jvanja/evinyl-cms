<?php
/**
 * @file
 * Contains \Drupal\evinyl_album\Controller\DislikeAlbumController.
 */
namespace Drupal\evinyl_album\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class DislikeAlbumController {
  public function content($id = 0) {


    $user = \Drupal::currentUser();
    $logged_user_id = $user->id();
    $this->remove_album_like($id, $logged_user_id);
    return new JsonResponse(array(
      'success' => TRUE,
      'message' => 'success'), 200, ['Content-Type'=> 'application/json']);
  }

  protected function remove_album_like($nid, $uid) {
    $loaded_album =  \Drupal\node\Entity\Node::load($nid);
    $likes_array = $loaded_album->field_likes->getValue();
    $searchForValue = $this->hasValue($likes_array, $uid);
    unset($likes_array[$searchForValue[1]]);
    $loaded_album->set('field_likes', $likes_array);
    $loaded_album->save();

    \Drupal::logger('evinyl_album')->notice('Removed like from ' . $nid . ' by ' . $uid);
  }

  /**
   * Helper function for finding if node field has a value
   */
  protected function hasValue($fieldArray, $value) {
    $counter = 0;
    foreach ($fieldArray as $field) {
      if ($field["value"] == $value) {
        return array(TRUE, $counter);
      }
      $counter ++;
    }
    if ($counter == 0) {
      return array(FALSE, -1);
    }
  }
}
