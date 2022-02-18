<?php

namespace Drupal\Tests\adv_varnish\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the Advanced Varnish cache can cache node object.
 *
 * @group adv_varnish
 */
abstract class AdvVarnishTestBase extends BrowserTestBase {

  /**
   * Defines default TTL value.
   */
  const PAGE_CACHE_MAXIMUM_AGE = '32400';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'adv_varnish',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    \Drupal::service('module_installer')->uninstall(['big_pipe']);
    $this->resetAll();

    // Create Basic page and Article node types.
    if ($this->profile !== 'standard') {
      $this->drupalCreateContentType([
        'type' => 'page',
        'name' => 'Basic page',
        'display_submitted' => FALSE,
      ]);
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }

    // Set up our custom test config.
    $config = $this->config('adv_varnish.cache_settings');
    $config->set('general', ['page_cache_maximum_age' => static::PAGE_CACHE_MAXIMUM_AGE]);
    $config->set('available', ['enable_cache' => TRUE]);
    $config->save();
  }

}
