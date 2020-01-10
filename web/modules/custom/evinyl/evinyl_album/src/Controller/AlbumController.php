<?php
/**
 * @file
 * Contains \Drupal\evinyl_album\Controller\AlbumController.
 */
namespace Drupal\evinyl_album\Controller;
use Drupal\node\Entity\Node;

class AlbumController {
  public function content($id = 0) {
      // Get an array of all 'article' node ids.
  // $article_nids = \Drupal::entityQuery('node')
  //   ->condition('type', 'album')
  //   ->execute();

  // Load all the articles.
  // $articles = Node::loadMultiple($article_nids);
  // foreach ($articles as $article) {
  //   $article->save();
  // }

  /* ==========================================================================

    ========================================================================== */

    // use Drupal\node\Entity\Node;
    //
    // $node = Node::load($nid);
    // //set value for field
    // $node->body->value = 'body';
    // $node->body->format = 'full_html';
    // //field tag
    // $node->field_tags = [1];
    // //field image
    // $field_image = array(
    //   'target_id' => $fileID,
    //   'alt' => "My 'alt'",
    //   'title' => "My 'title'",
    // );
    // $node->field_image = $field_image;
    //
    // $node->save();


    return array(
      '#type' => 'markup',
      '#markup' => t('Not neccessary any more. Use core JSON:API'),
    );
  }
}
