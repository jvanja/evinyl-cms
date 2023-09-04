<?php

/**
 * @file
 * Contains \Drupal\evinyl_lyrics\Controller\EvinylLyricsController.
 */

namespace Drupal\evinyl_lyrics\Controller;

use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\HttpFoundation\JsonResponse;

class EvinylLyricsController
{
  public function content()
  {
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body);
    $lyrics = $data->lyrics;
    $paragraphId = $data->id;

    try {
      $paragraph = Paragraph::load($paragraphId);
      $paragraph->set('field_lyrics', $lyrics);
      $paragraph->save();
      return new JsonResponse(['data' => $data, 'status' => 200]);
    } catch (\Throwable $th) {
      return new JsonResponse(['data' => $th, 'status' => 500]);
    }
  }
}
