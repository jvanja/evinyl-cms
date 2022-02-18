<?php

namespace Drupal\adv_varnish;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Interface RequestHandlerInterface.
 *
 * @package Drupal\adv_varnish
 */
interface RequestHandlerInterface {

  /**
   * Header to store Grace TTL.
   */
  const HEADER_GRACE = 'X-Grace';

  /**
   * Header to store TTL value.
   */
  const HEADER_TTL = 'X-TTL';

  /**
   * Header used to inform Varnish that we need to support ESI.
   */
  const HEADER_X_DOESI = 'X-DOESI';

  /**
   * Header which is used to see debug Varnish headers.
   */
  const HEADER_CACHE_DEBUG = 'X-Cache-Debug';

  /**
   * Header for Advanced varnish status indication.
   */
  const HEADER_ADV_VARNISH_STATUS = 'X-Adv-Varnish';

  /**
   * Header for Advanced varnish status indication.
   */
  const HEADER_DEFLATE_KEY = 'X-Deflate-Key';

  /**
   * Header which is used to handle tags.
   */
  const HEADER_CACHE_TAG = 'X-Tag';

  /**
   * ESI type for user blocks data.
   */
  const CACHE_TAG_USER_BLOCKS = 'user_blocks';

  /**
   * Header which is used to store secret tag.
   */
  const HEADER_SECRET_TAG = 'X-Varnish-Secret';

  /**
   * ESI type for user blocks data.
   */
  const ESI_TYPE_USER_BLOCKS = 'user_blocks';

  /**
   * ESI type for default blocks.
   */
  const ESI_TYPE_BLOCK = 'block';

  /**
   * Cache data per page.
   */
  const CACHE_PER_PAGE = 1;

  /**
   * Cache data per role.
   */
  const CACHE_PER_ROLE = 2;

  /**
   * Cache data per user.
   */
  const CACHE_PER_USER = 3;

  /**
   * Response event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function handleResponseEvent(ResponseEvent $event);

}
