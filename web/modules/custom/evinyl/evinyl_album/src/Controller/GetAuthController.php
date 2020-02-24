<?php
/**
 * @file
 * Contains \Drupal\evinyl_album\Controller\GetJsonController.
 */
namespace Drupal\evinyl_album\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session;

class GetAuthController {
  public function content() {

    /**
 * Get an auth token for the current user.
 */
// function nodejs_auth_get_token(SessionInterface $session) {
//   $nodejs_auth_get_token_callback = \Drupal::config('nodejs.config')->get('auth_get_token_callback');
//   if (!function_exists($nodejs_auth_get_token_callback)) {
//     throw new Exception("Cannot proceed without a valid nodejs_auth_get_token callback - looked for '$nodejs_auth_get_token_callback'.");
//   }
//   return $nodejs_auth_get_token_callback($session);
// }
//
    //
  $response = new JsonResponse();
  $session = \Drupal::request()->getSession();

  // $response->setData(nodejs_auth_get_token($_SESSION));
  $response->setData(['authToken' => nodejs_auth_get_token($session)]);
  return $response;

    // return array(
    //   '#type' => 'markup',
    //   '#markup' => t('New albums.json generated'),
    // );
  }
}
