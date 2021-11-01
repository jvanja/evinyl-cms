<?php

namespace Drupal\mass_contact\Tests\Form;

use Drupal\KernelTests\ConfigFormTestBase;
use Drupal\mass_contact\Form\EmailBodyForm;

/**
 * Admin settings form test.
 *
 * @group mass_contact
 */
class EmailBodyFormTest extends ConfigFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'mass_contact',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();

    $this->installConfig(['filter']);
    $this->installEntitySchema('user');
    $this->form = EmailBodyForm::create($this->container);
    $values = [
      // @todo
    ];
    $this->values = [];
    foreach ($values as $config_key => $value) {
      $this->values[$config_key] = [
        '#value' => $value,
        '#config_name' => 'mass_contact.settings',
        '#config_key' => $config_key,
      ];
    }
  }

}
