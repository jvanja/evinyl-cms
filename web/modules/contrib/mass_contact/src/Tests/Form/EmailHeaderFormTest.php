<?php

namespace Drupal\mass_contact\Tests\Form;

use Drupal\KernelTests\ConfigFormTestBase;
use Drupal\mass_contact\Form\EmailHeaderForm;

/**
 * Admin settings form test.
 *
 * @group mass_contact
 */
class EmailHeaderFormTest extends ConfigFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'mass_contact',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();

    $this->form = EmailHeaderForm::create($this->container);
    $values = [
      'character_set' => 'UTF-7',
      'default_sender_name' => $this->randomString(),
      'default_sender_email' => mb_strtolower($this->randomMachineName()) . '@drupal.org',
      'include_from_name' => TRUE,
      'include_to_name' => TRUE,
      'use_bcc' => NULL,
      'category_override' => NULL,
    ];
    foreach ($values as $config_key => $value) {
      $this->values[$config_key] = [
        '#value' => $value,
        '#config_name' => 'mass_contact.settings',
        '#config_key' => $config_key,
      ];
    }
  }

}
