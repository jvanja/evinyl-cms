<?php
/**
 * @file
 * Contains \Drupal\evinyl_album\Controller\GetJsonController.
 */
namespace Drupal\evinyl_album\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class GetJsonController {
  public function content() {

    if($_SERVER['SERVER_NAME'] == 'localhost') {
      $wget = '/usr/local/bin/wget --header="Accept: application/vnd.api+json"';
      $api = ' -O album.json "http://localhost/evinyl8/web/jsonapi/node/album';
    } else {
      $wget = '/usr/bin/wget --header="Accept: application/vnd.api+json"';
      $api = ' -O album.json "https://lab.evinyl.net/jsonapi/node/album';
    }
    $include = '?include=field_genre,field_artist_term,field_image&filter[status][value]=1"';
    $command = $wget . $api . $include;

    $result = exec($command . ' > /dev/null &', $response, $return_code);
    return array(
      '#type' => 'markup',
      '#markup' => t('New albums.json generated'),
    );
  }
}
