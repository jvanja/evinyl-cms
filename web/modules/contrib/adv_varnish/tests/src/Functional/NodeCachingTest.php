<?php

namespace Drupal\Tests\adv_varnish\Functional;

/**
 * Tests that the Advanced Varnish cache can cache node object.
 *
 * @group adv_varnish_node_test
 */
class NodeCachingTest extends AdvVarnishTestBase {

  /**
   * Default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'classy';

  /**
   * Tests that the Anonymous user get cached page.
   */
  public function testAdvVarnishAnonymousCache() {
    // Create a user, with edit/delete own content permission.
    $test_user1 = $this->drupalCreateUser([
      'access content',
      'edit own page content',
      'delete own page content',
    ]);

    $this->drupalLogin($test_user1);

    $node1 = $this->createNode(['type' => 'page']);
    $this->drupalLogout();

    $this->drupalGet('node/' . $node1->id());
    $this->assertSession()->responseHeaderEquals('X-TTL', static::PAGE_CACHE_MAXIMUM_AGE);
    $this->assertSession()->responseHeaderEquals('X-Adv-Varnish', 'Cache-enabled');
  }

  /**
   * Tests that the Registered user get cached page.
   */
  public function testAdvVarnishRegisteredCache() {
    // Create a user, with edit/delete own content permission.
    $test_user1 = $this->drupalCreateUser([
      'access content',
      'edit own page content',
      'delete own page content',
    ]);

    $this->drupalLogin($test_user1);

    $node1 = $this->createNode(['type' => 'page']);
    $this->drupalLogout();

    // Set up our custom test config.
    $config = $this->config('adv_varnish.cache_settings');
    $config->set('available', ['enable_cache' => TRUE, 'authenticated_users' => TRUE]);
    $config->set('general', ['page_cache_maximum_age' => static::PAGE_CACHE_MAXIMUM_AGE]);
    $config->save();

    // Create a user without edit/delete permission.
    $test_user2 = $this->drupalCreateUser([
      'access content',
    ]);

    $this->drupalLogin($test_user2);
    $this->drupalGet('node/' . $node1->id());
    $this->assertSession()->responseHeaderEquals('X-TTL', static::PAGE_CACHE_MAXIMUM_AGE);
    $this->assertSession()->responseHeaderEquals('X-Adv-Varnish', 'Cache-enabled');
  }

  /**
   * Tests that the Excluded URL's settings.
   */
  public function testAdvVarnishExcludedUrls() {
    // Create a user, with edit/delete own content permission.
    $test_user1 = $this->drupalCreateUser([
      'access content',
      'edit own page content',
      'delete own page content',
      'administer advanced varnish configuration',
    ]);

    $this->drupalLogin($test_user1);

    $node1 = $this->createNode(['type' => 'page']);

    $this->drupalGet('admin/config/development/adv_varnish');
    $edit = [
      'adv_varnish[available][enable_cache]' => TRUE,
      'adv_varnish[available][excluded_urls]' => '*|*',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->drupalLogout();
    drupal_flush_all_caches();

    $this->drupalGet('node/' . $node1->id());
    $this->assertSession()->responseHeaderEquals('X-Adv-Varnish', 'Cache-disabled');
  }

  /**
   * Tests that the Cache-Control Headers.
   */
  public function testAdvVarnishCacheControlHeaders() {
    // Create a user, with edit/delete own content permission.
    $test_user1 = $this->drupalCreateUser([
      'access content',
      'edit own page content',
      'delete own page content',
    ]);

    $this->drupalLogin($test_user1);
    $node1 = $this->createNode(['type' => 'page']);

    // Set up our custom test config.
    $config = $this->config('adv_varnish.cache_settings');
    $config->set('available', ['enable_cache' => TRUE, 'authenticated_users' => TRUE]);
    $config->set('cache_control', [
      'anonymous' => '/node/' . $node1->id() . '|max-age=2000, public' . PHP_EOL,
      'authenticated' => '/node/' . $node1->id() . '|max-age=3000, public' . PHP_EOL,
    ]);
    $config->save();

    $this->drupalGet('/node/' . $node1->id());
    $this->assertSession()->responseHeaderEquals('Cache-Control', 'max-age=3000, public');
    $this->drupalLogout();

    $this->drupalGet('/node/' . $node1->id());
    $this->assertSession()->responseHeaderEquals('Cache-Control', 'max-age=2000, public');
  }

  /**
   * Tests possibility to override TTL per node bundle.
   */
  public function testAdvVarnishOverrideTtl() {
    // Set custom TTL value.
    $ttl = '600';

    // Set up our custom test config.
    $config = $this->config('adv_varnish.cache_settings');
    $config->set('available', ['enable_cache' => TRUE, 'authenticated_users' => TRUE]);
    $config->save();

    // Create user with "administer content types" permission.
    $account = $this->drupalCreateUser(['administer content types']);
    $this->drupalLogin($account);

    // Set custom TTL for Article content type.
    $this->drupalGet('admin/structure/types/manage/article');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'adv_varnish[override]' => TRUE,
      'adv_varnish[ttl]' => $ttl,
    ];
    $this->submitForm($edit, 'Save content type');

    // Create Article and Blog post.
    $article = $this->createNode(['type' => 'article']);
    $page = $this->createNode(['type' => 'page']);

    $this->drupalLogout();

    // Check that out custom TTL applied for Article.
    $this->drupalGet('node/' . $article->id());
    $this->assertSession()->responseHeaderEquals('X-TTL', $ttl);

    // Check that we use default TTL on Blog post.
    $this->drupalGet('node/' . $page->id());
    $this->assertSession()->responseHeaderEquals('X-TTL', static::PAGE_CACHE_MAXIMUM_AGE);
  }

}
