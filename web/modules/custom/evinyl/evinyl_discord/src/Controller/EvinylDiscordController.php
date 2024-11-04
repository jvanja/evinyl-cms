<?php

/**
 * @file
 * Contains \Drupal\hello_world\Controller\EvinylDiscordController.
 */

namespace Drupal\evinyl_discord\Controller;

use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\RequestException;
use \Drupal\Core\Utility\Error;

/**
 * Provides a REST resource for displaying Discord messages.
 *
 */
class EvinylDiscordController {

  /**
   * Guzzle\Client instance.
   *
   * @var \Guzzle\Client
   */
  private $httpClient;

  public function __construct() {
    $this->httpClient = new Client();
  }

  /**
   * Retrieves a list of Discord messages.
   *
   * @return array
   *   A list of Discord messages as an array.
   */
  public function get() {

    $messages = $this->getDiscordMessages();
    $response = new Response();
    $response->setContent($messages->getBody());
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

  protected function getDiscordMessages() {
    $logger = \Drupal::logger('evinyl_discord');
    $config = \Drupal::config('evinyl_discord.settings');
    $discord_token = $config->get('discord_token');
    $discord_channel_id = $config->get('discord_channel_id');

    $headers = [
      'Content-Type' => 'application/json',
      'authorization' => 'Bot ' . $discord_token,
    ];
    // use Guzzle to fetch messages from the Discord API
    try {
      $response = $this->httpClient->request('GET', 'https://discord.com/api/channels/' . $discord_channel_id . '/messages', ['headers' => $headers]);;
      return $response;
    } catch (ServerException $e) {
      $logger->error('Server error! Something went wrong with the request.', ['exception' => $e]);
      return FALSE;
    } catch (RequestException $e) {
      $logger->error('Server error! Something went wrong with the request.', ['exception' => $e]);
      return FALSE;
    }
  }
}
