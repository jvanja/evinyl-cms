<?php

namespace Drupal\Tests\social_auth_facebook\Functional;

use Drupal\Tests\social_auth\Functional\SocialAuthTestBase;

/**
 * Test Social Auth Facebook settings form.
 *
 * @group social_auth
 *
 * @ingroup social_auth_facebook
 */
class SocialAuthFacebookSettingsFormTest extends SocialAuthTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
<<<<<<< HEAD
  protected static $modules = ['social_auth_facebook'];
=======
  public static $modules = ['social_auth_facebook'];
>>>>>>> 25e9ac38d (added login facebook)

  /**
   * {@inheritdoc}
   */
<<<<<<< HEAD
  protected function setUp(): void {
=======
  protected function setUp() {
>>>>>>> 25e9ac38d (added login facebook)
    $this->module = 'social_auth_facebook';
    $this->provider = 'facebook';
    $this->moduleType = 'social-auth';

    parent::setUp();
  }

  /**
   * Test if implementer is shown in the integration list.
   */
  public function testIsAvailableInIntegrationList() {
    $this->fields = ['client_id', 'client_secret'];

    $this->checkIsAvailableInIntegrationList();
  }

  /**
   * Test if permissions are set correctly for settings page.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testPermissionForSettingsPage() {
    $this->checkPermissionForSettingsPage();
  }

  /**
   * Test settings form submission.
   */
  public function testSettingsFormSubmission() {
    $this->edit = [
      'app_id' => $this->randomString(10),
      'app_secret' => $this->randomString(10),
      'graph_version' => '2.10',
    ];

    $this->checkSettingsFormSubmission();
  }

}
