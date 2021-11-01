<?php

namespace Drupal\Tests\mass_contact\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Base test class for kernel tests for Mass Contact.
 */
abstract class MassContactTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter', 'mass_contact', 'user', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();

    $this->installEntitySchema('mass_contact_category');
  }

}
