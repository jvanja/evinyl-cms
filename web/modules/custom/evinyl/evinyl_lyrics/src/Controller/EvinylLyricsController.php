<?php

/**
 * @file
 * Contains \Drupal\evinyl_lyrics\Controller\EvinylLyricsController.
 */

namespace Drupal\evinyl_lyrics\Controller;

use Drupal\paragraphs\Entity\Paragraph;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\JsonResponse;

class EvinylLyricsController {

  public function content() {
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body);
    $artistName = $data->artistName;
    $songName = $data->songName;
    $paragraphId = $data->id;

    $apiEndPoint = 'https://api.musixmatch.com/ws/1.1/matcher.lyrics.get';
    $params = [
      'query' => [
        'q_artist' => $artistName,
        'q_track' => $songName,
        'apikey' => 'd778b574003da2f491a96371018c912a',
      ],
    ];
    $client = new Client();
    $res = $client->request('GET', $apiEndPoint, $params);

    if ($res->getStatusCode() === 200) {
      return $this->updateParapgraphs($paragraphId, $res);
    }

    return new JsonResponse(['data' => NULL, 'status' => $res->getStatusCode()]);
  }

  public function updateParapgraphs($paragraphId, $musicMatchResponse) {
    $responseObject = json_decode($musicMatchResponse->getBody(), TRUE);
    $lyrics = str_replace("\n", '<br/>', $responseObject['message']['body']['lyrics']['lyrics_body']);
    $responseStatusCode = $responseObject['message']['header']['status_code'];

    if ($responseStatusCode === 200) {
      try {
        $paragraph = Paragraph::load($paragraphId);
        $paragraph->set('field_lyrics', $lyrics);
        $paragraph->save();

        return new JsonResponse(['data' => ['id' => $paragraphId, 'lyrics' => $lyrics], 'status' => 200]);
      }
      catch (\Throwable $th) {
        return new JsonResponse(['data' => $th, 'status' => 500]);
      }
    }
    else {
      return new JsonResponse(['status' => $responseStatusCode]);
    }
  }

}
