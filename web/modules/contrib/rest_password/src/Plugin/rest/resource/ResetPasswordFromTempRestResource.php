<?php

namespace Drupal\rest_password\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest_password\Event\PasswordResetEvent;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a resource to reset Drupal password for user.
 *
 * @RestResource(
 *   id = "lost_password_reset",
 *   label = @Translation("Reset Lost password Via Temp password"),
 *   uri_paths = {
 *     "canonical" = "/user/lost-password-reset",
 *     "create" = "/user/lost-password-reset"
 *   }
 * )
 */
class ResetPasswordFromTempRestResource extends ResourceBase {

  use StringTranslationTrait;
  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new ResetPasswordFromTempRestResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    UserStorageInterface $user_storage,
    EventDispatcherInterface $eventDispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
    $this->userStorage = $user_storage;
    $this->logger = $logger;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest_password'),
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Example {"name":"username", "temp_pass":"TEMPPASS", "new_pass": "NEWPASS"}.
   *
   * @param array $data
   *   The post data array.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   Returns ModifiedResourceResponse.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function post(array $data): ModifiedResourceResponse {
    $code = 400;
    $response = [];
    if (!empty($data['name']) && !empty($data['temp_pass']) && !empty($data['new_pass'])) {
      $name = $data['name'];
      $temp_pass = $data['temp_pass'];
      $new_pass = $data['new_pass'];

      // Try to load by username.
      $users = $this->userStorage->loadByProperties(['name' => $name]);
      // Try to load by email.
      if (empty($users)) {
        $users = $this->userStorage->loadByProperties(['mail' => $name]);
      }
      if (!empty($users)) {
        $account = reset($users);
        if ($account && $account->id()) {
          // Blocked accounts cannot request a new password.
          if (!$account->isActive()) {
            $response = $this->t('This account is blocked or has not been activated yet.');
          }
          else {
            // CHECK the temp password.
            $uid = $account->id();
            $service = \Drupal::service('tempstore.shared');
            $collection = 'rest_password';
            $tempstore = $service->get($collection, $uid);

            $temp_pass_from_storage = $tempstore->get('temp_pass_' . $uid);
            if (!empty($temp_pass_from_storage)) {
              // Trying to be a bit good. Issue #3036405.
              if (hash_equals($temp_pass_from_storage, $temp_pass) === TRUE) {
                // Cool.... lets change this password.
                $account->setPassword($new_pass);
                $event = new PasswordResetEvent($account);
                $this->eventDispatcher->dispatch($event, PasswordResetEvent::PRE_RESET);
                $account->save();
                $this->eventDispatcher->dispatch($event, PasswordResetEvent::POST_RESET);
                $code = 200;
                $response = ['message' => $this->t('Your New Password has been saved please log in.')];
                // Delete temp password because next time it will be not valid.
                $tempstore->delete('temp_pass_' . $uid);

              }
              else {
                $response = ['message' => $this->t('The recovery password is not valid.')];
              }
            }
            else {
              $response = ['message' => $this->t('No valid temp password request.')];
            }
          }
        }
      }
      else {
        $response = ['message' => $this->t('This User was not found or invalid')];
      }
    }
    else {
      $response = ['message' => $this->t('name, new_pass, and temp_pass fields are required')];
    }

    return new ModifiedResourceResponse($response, $code);
  }

}
