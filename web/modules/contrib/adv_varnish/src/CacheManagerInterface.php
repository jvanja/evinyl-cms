<?php

namespace Drupal\adv_varnish;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Interface CacheManagerInterface.
 */
interface CacheManagerInterface {

  /**
   * Varnish deflate path.
   *
   * For more info refer to default.vcl file.
   */
  const DEFLATE_PATH = '/deflate';

  /**
   * Flush cache of all site.
   *
   * @return bool
   *   Result of the cache flushing.
   */
  public function flushAllCaches() : bool;

  /**
   * Flush cache by tag.
   *
   * @param array $tags
   *   Tags name to be flushed.
   *
   * @return bool
   *   Result of purging.
   */
  public function purgeTags(array $tags) : bool;

  /**
   * Flush cache by URL.
   *
   * @param string $uri
   *   Tag name to be flushed.
   *
   * @return bool
   *   Result of purging.
   */
  public function purgeUri(string $uri) : bool;

  /**
   * Maps cache tags to hashes.
   *
   * Used when the X-Tag/Surrogate-Key/X-Drupal-Cache-Tags header size otherwise
   * exceeds 16 KB.
   *
   * @param string[] $cache_tags
   *   The cache tags in the header.
   *
   * @return string[]
   *   The hashes to use instead in the header.
   */
  public static function cacheTagsToHashes(array $cache_tags);

  /**
   * Define if esi enabled for this page.
   *
   * @return bool
   *   ESI enable state.
   */
  public function esiEnabled();

  /**
   * Specific entity cache settings getter.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   *
   * @return array
   *   Set of caching settings for current request.
   */
  public function getCacheSettings(ResponseEvent $event);

  /**
   * Define if caching enabled for the page and we can proceed with the request.
   *
   * @return bool
   *   Result of varnish enable state.
   */
  public function cachingEnabled();

  /**
   * Deflate varnish cache for the given value.
   *
   * @param int $number
   *   Value to deflate in varnish.
   *
   * @return bool
   *   Was request successful or not.
   */
  public function deflateCache($number): bool;

  /**
   * Define if this is an ESI request.
   *
   * @return bool
   *   ESI enable state.
   */
  public function isEsiRequest();

  /**
   * Purge user blocks if required.
   */
  public function purgeUserBlocks();

}
