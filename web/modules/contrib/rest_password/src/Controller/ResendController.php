<?php

namespace Drupal\rest_password\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * A controller to send password reset email.
 */
class ResendController extends ControllerBase {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new \Drupal\rest_password\Controller\ResendController object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
    );
  }

  /**
   * Resends token mail to verify the account.
   *
   * @param object $user
   *   The user.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   */
  public function resend($user, Request $request) {
    // Check if account is unverified.
    if (!empty($user->mail)) {
      // Send email to verify account.
      _rest_password_user_mail_notify('password_reset_rest', $user);
      $this->messenger->addStatus('Password reset email was sent to ' . $user->getAccountName() . '.');
    }
    else {
      $this->messenger->addError('There was an error sending the password reset mail!');
    }
    $previousUrl = $request->headers->get('referer');
    $response = new RedirectResponse($previousUrl);
    return $response;
  }

}
