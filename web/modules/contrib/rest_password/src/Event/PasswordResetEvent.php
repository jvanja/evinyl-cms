<?php

namespace Drupal\rest_password\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\user\UserInterface;

/**
 * Password reset Event.
 */
class PasswordResetEvent extends Event {
  const PRE_RESET = 'event.pre_rest_password_reset';
  const POST_RESET = 'event.post_rest_password_reset';

  /**
   * User.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Event constructor.
   *
   * @param \Drupal\user\UserInterface $user
   *   User.
   */
  public function __construct(UserInterface $user) {
    $this->user = $user;
  }

  /**
   * Get user.
   *
   * @return \Drupal\user\UserInterface
   *   User entity.
   */
  public function getUser(): UserInterface {
    return $this->user;
  }

}
