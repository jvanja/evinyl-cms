<?php

/**
 * @file
 * Contains \Drupal\evinyl_album\Controller\GetJsonController.
 */
namespace Drupal\evinyl_album\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class GetAuthController {

  public function content() {
    $response = new JsonResponse();
    $session = \Drupal::request()->getSession();

    if (function_exists('nodejs_auth_get_token')) {
      /** @disregard The function is exposed via the nodejs module */
      $response->setData(['authToken' => nodejs_auth_get_token($session)]);
    } else {
      $response->setData(['authToken' => '']);
    }
    return $response;
  }
}
