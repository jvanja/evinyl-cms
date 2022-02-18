<?php

namespace Drupal\Tests\adv_varnish\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the Advanced Varnish UI pages are reachable.
 *
 * @group adv_varnish_settings
 */
class UiPageTest extends BrowserTestBase {

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
  protected static $modules = ['adv_varnish'];

  /**
   * Tests that the Form page is reachable.
   */
  public function testAdvVarnishSettingsPage() {
    $account = $this->drupalCreateUser(['administer advanced varnish configuration']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/config/development/adv_varnish');
    $this->assertSession()->statusCodeEquals(200);

    // Test that there is a General settings group present.
    $this->assertSession()->pageTextContains('General settings');

    // Test access without the permission
    // 'administer advanced varnish configuration'.
    $this->drupalLogin($this->drupalCreateUser(['access administration pages']));
    $this->drupalGet('admin/config/development/adv_varnish');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test that we can see that request pass through AdvVarnish.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testAdvVarnishWorks() {
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals('X-Adv-Varnish', 'Cache-disabled');
  }

  /**
   * Test Image toolkit setup form.
   */
  public function testAdvVarnishForm() {
    $account = $this->drupalCreateUser(['administer advanced varnish configuration']);
    $this->drupalLogin($account);

    // Get form.
    $this->drupalGet('admin/config/development/adv_varnish');

    // Test changing enable checkbox.
    $edit = ['adv_varnish[available][enable_cache]' => TRUE];
    $this->submitForm($edit, 'Save configuration');
    $config = $this->config('adv_varnish.cache_settings');
    $this->assertEquals(TRUE, $config->get('available.enable_cache'));

    // Test changing enable authenticated_users checkbox.
    $this->drupalGet('admin/config/development/adv_varnish');
    $edit = ['adv_varnish[available][authenticated_users]' => TRUE];
    $this->submitForm($edit, 'Save configuration');
    $config = $this->config('adv_varnish.cache_settings');
    $this->assertEquals(TRUE, $config->get('available.authenticated_users'));

    // Test changing enable esi checkbox.
    $this->drupalGet('admin/config/development/adv_varnish');
    $edit = ['adv_varnish[available][esi]' => TRUE];
    $this->submitForm($edit, 'Save configuration');
    $config = $this->config('adv_varnish.cache_settings');
    $this->assertEquals(TRUE, $config->get('available.esi'));

    // Test changing enable logging checkbox.
    $this->drupalGet('admin/config/development/adv_varnish');
    $edit = ['adv_varnish[general][logging]' => TRUE];
    $this->submitForm($edit, 'Save configuration');
    $config = $this->config('adv_varnish.cache_settings');
    $this->assertEquals(TRUE, $config->get('general.logging'));

    // Test changing enable debug checkbox.
    $this->drupalGet('admin/config/development/adv_varnish');
    $edit = ['adv_varnish[general][debug]' => TRUE];
    $this->submitForm($edit, 'Save configuration');
    $config = $this->config('adv_varnish.cache_settings');
    $this->assertEquals(TRUE, $config->get('general.debug'));

    // Test changing enable grace time.
    $this->drupalGet('admin/config/development/adv_varnish');
    $edit = ['adv_varnish[general][grace]' => 600];
    $this->submitForm($edit, 'Save configuration');
    $config = $this->config('adv_varnish.cache_settings');
    $this->assertEquals(600, $config->get('general.grace'));

    // Test changing enable page_cache_maximum_age time.
    $this->drupalGet('admin/config/development/adv_varnish');
    $edit = ['adv_varnish[general][page_cache_maximum_age]' => 900];
    $this->submitForm($edit, 'Save configuration');
    $config = $this->config('adv_varnish.cache_settings');
    $this->assertEquals(900, $config->get('general.page_cache_maximum_age'));

    // Test enable varnish terminal settings.
    $this->drupalGet('admin/config/development/adv_varnish');
    $edit = [
      'adv_varnish[general][varnish_purger]' => TRUE,
      'adv_varnish[general][varnish_server]' => '127.0.0.1',
      'adv_varnish[general][secret]' => '127.0.0.1',
    ];
    $this->submitForm($edit, 'Save configuration');
    $config = $this->config('adv_varnish.cache_settings');
    $this->assertEquals('127.0.0.1', $config->get('general.varnish_server'));
    $this->assertEquals(TRUE, $config->get('general.varnish_purger'));
  }

}
