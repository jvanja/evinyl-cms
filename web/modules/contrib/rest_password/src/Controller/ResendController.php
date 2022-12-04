<?php

namespace Drupal\rest_password\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ResendController.
 */
class ResendController extends ControllerBase {
  public function resend($user, Request $request) {
    // Check if account is unverified.
    if (!empty($user->mail)) {
      // Resend verify account token mail.
      _rest_password_user_mail_notify('password_reset_rest', $user);
      \Drupal::messenger()->addStatus('Password reset email was sent to ' . $user->getAccountName() . '.');
    } else {
      \Drupal::messenger()->addError('There was an error sending the password reset mail!');
    }
    $previousUrl = $request->headers->get('referer');
    $response = new RedirectResponse($previousUrl);
    return $response;
  }
}
