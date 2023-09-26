<?php

namespace Drupal\rest_password\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ResendController.
 */
class ResendController extends ControllerBase {

  protected $messenger;

  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
    );
  }

  public function resend($user, Request $request) {
    // Check if account is unverified.
    if (!empty($user->mail)) {
      // Resend verify account token mail.
      _rest_password_user_mail_notify('password_reset_rest', $user);
      $this->messenger->addStatus('Password reset email was sent to ' . $user->getAccountName() . '.');
    } else {
      $this->messenger->addError('There was an error sending the password reset mail!');
    }
    $previousUrl = $request->headers->get('referer');
    $response = new RedirectResponse($previousUrl);
    return $response;
  }
}
