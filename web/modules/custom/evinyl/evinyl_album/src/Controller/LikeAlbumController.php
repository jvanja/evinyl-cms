<?php
/**
 * @file
 * Contains \Drupal\evinyl_album\Controller\LikeAlbumController.
 */
namespace Drupal\evinyl_album\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class LikeAlbumController {
  public function content($id = 0) {


    $user = \Drupal::currentUser()->id();
    $logged_user_id = $user;
    $this->add_album_like($id, $logged_user_id);
    return new JsonResponse(array(
      'success' => TRUE,
      'message' => 'success'), 200, ['Content-Type'=> 'application/json']);
  }

  protected function add_album_like($nid, $uid) {
    $loaded_album =  \Drupal\node\Entity\Node::load($nid);
    $likes_array = $loaded_album->field_likes->getValue();
    array_push( $likes_array, array('value' => $uid));
    $loaded_album->set('field_likes', $likes_array);
    $loaded_album->save();
    \Drupal::logger('evinyl_album')->notice('Added like to ' . $nid . ' by ' . $uid);
  }

}
