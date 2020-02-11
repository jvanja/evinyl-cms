<?php

namespace Drupal\userswitch\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\userswitch\UserSwitch;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides route responses for the Example module.
 */
class UserSwitchController extends ControllerBase {

  protected $userSwitch;
  protected $currentUser;
  protected $database;
  protected $messenger;

  /**
   * Constructs a new UserSwitchController object.
   */
  public function __construct(AccountInterface $currentUser, UserSwitch $userSwitch, Connection $database, MessengerInterface $messenger) {
    $this->currentUser = $currentUser;
    $this->userswitch = $userSwitch;
    $this->database = $database;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
                  $container->get('current_user'), $container->get('userswitch'), $container->get('database'), $container->get('messenger')
          );
  }

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function userSwitchList() {

    $_uid = $this->currentUser->id();
    $query = $this->database->select('users_field_data', 'u');
    $query->fields('u', ['uid', 'name', 'mail']);
    // For the pagination we need to extend the pagerselectextender and
    // limit in the query.
    $query->condition('uid', $_uid, '!=');
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
    $results = $pager->execute()->fetchAll();

    // Initialize an empty array.
    $output = [];
    $header = ['#', 'Name', 'Mail', 'Operations'];
    // Next, loop through the $results array.
    foreach ($results as $result) {
      if ($result->uid != 0) {

        $url = Url::fromUri('internal:/admin/people/user/' . $result->uid);
        $_link = Link::fromTextAndUrl($this->t('Click Here'), $url);
        $output[$result->uid] = [
          'userid' => $result->uid,
          'Username' => $result->name,
          'email' => $result->mail,
          'link' => $_link,
        ];
      }
    }

    $element[] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $output,
    ];

    return $element;
  }

  /**
   * Switch to new user.
   */
  public function switchuser($uid) {
    if ($this->userswitch->switchToOther($uid)) {
      $message = $this->t('You are now @user.', ['@user' => $this->currentUser->getDisplayName()]);
      $this->messenger->addMessage($message);
    }

    $url = Url::fromRoute('entity.user.canonical', ['user' => $uid])->toString();
    $response = new RedirectResponse($url);
    $response->send();
    return new Response();
  }

  /**
   * Switch back to original user.
   */
  public function switchbackuser() {
    // Store current user name for messages.
    $account_name = $this->currentUser->getDisplayName();
    $get_uid = $this->userswitch->getUserId();

    if ($get_uid) {
      if ($this->userswitch->switchUserBack()) {
        $message = $this->t('Switch account as @user.', ['@user' => $account_name]);
        $this->messenger->addMessage($message);
      }
      else {
        $message = $this->t('Error trying as @user.', ['@user,' => $account_name]);
        $this->messenger->addMessage($message, $this->messenger::TYPE_ERROR);
      }
      $url = Url::fromRoute('entity.user.canonical', ['user' => $get_uid])->toString();
    }
    else {
      $url = Url::fromRoute('user.admin_index');
    }

    $response = new RedirectResponse($url);
    $response->send();
    return new Response();
  }

  /**
   * Checks access for this controller.
   */
  public function getUserSwitchPermissions() {
    if ($this->userswitch->isSwitchUser()) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
