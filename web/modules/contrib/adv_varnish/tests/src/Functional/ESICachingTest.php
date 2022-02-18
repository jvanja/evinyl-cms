<?php

namespace Drupal\Tests\adv_varnish\Functional;

use Drupal\block\Entity\Block;

/**
 * Tests ESI functionality and ttl for blocks.
 *
 * @group adv_varnish_esi_test
 */
class ESICachingTest extends AdvVarnishTestBase {

  /**
   * Default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'classy';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['adv_varnish_block_test', 'block'];

  /**
   * Tests that blocks support ttl and esi functionality.
   */
  public function testEsiAdvVarnishDefaultBlockCache() {
    $ttl = '60';

    $plugin_id = 'simple_block';
    $block_id = strtolower($plugin_id . '_' . $this->randomMachineName());
    $config = \Drupal::configFactory();

    $data = [
      'plugin' => $plugin_id,
      'region' => 'sidebar_first',
      'id' => $block_id,
      'theme' => $config->get('system.theme')->get('default'),
      'label' => 'Block label',
      'visibility' => [],
      'weight' => 0,
      'settings' => [
        'cache' => [
          'esi' => 1,
          'cachemode' => 1,
          'ttl' => $ttl,
        ],
      ],
    ];
    $block = Block::create($data);
    $block->save();

    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Simple block');

    $this->drupalGet('/adv_varnish/esi/block/' . $block_id);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Simple block');

    $this->assertSession()->responseHeaderEquals('X-TTL', $ttl);
    $this->assertSession()->responseHeaderEquals('X-Adv-Varnish', 'Cache-enabled');
  }

  /**
   * Tests the plugin for user blocks working with ESI.
   */
  public function testEsiAdvVarnishUserBlockCache() {
    global $base_url;

    $query = '//span[@class="adv-varnish-user-blocks-wrapper"]/esi/@src';

    // Set up our custom test config.
    $config = $this->config('adv_varnish.cache_settings');
    $config->set('general', ['page_cache_maximum_age' => static::PAGE_CACHE_MAXIMUM_AGE]);
    $config->set('available', [
      'enable_cache' => TRUE,
      'authenticated_users' => TRUE,
      'esi' => TRUE,
    ]);
    $config->save();

    // Create a user, with edit/delete own content permission.
    $u1_name = 'user_1';
    $user1 = $this->drupalCreateUser([
      'access content',
      'edit own page content',
      'delete own page content',
    ], $u1_name);

    $this->drupalLogin($user1);
    $this->drupalGet('<front>');

    $xpath = $this->assertSession()->buildXPathQuery($query);
    $result = $this->xpath($xpath);

    // Get path to esi block.
    $esi_path = $result[0]->getText();
    $this->drupalGet($base_url . $esi_path);
    $this->assertSession()->responseContains("Hi, {$u1_name}");
    $this->assertEquals(1, count($result));
    $this->drupalLogout();

    // Create a user, with edit/delete own content permission.
    $u2_name = 'user_2';
    $user2 = $this->drupalCreateUser([
      'access content',
      'edit own page content',
      'delete own page content',
    ], $u2_name);

    $this->drupalLogin($user2);
    $this->drupalGet('<front>');

    $xpath = $this->assertSession()->buildXPathQuery($query);
    $result = $this->xpath($xpath);

    // Get path to esi block.
    $esi_path = $result[0]->getText();
    $this->drupalGet($base_url . $esi_path);
    $this->assertSession()->responseContains("Hi, {$u2_name}");
    $this->assertEquals(1, count($result));
    $this->drupalLogout();
  }

}
