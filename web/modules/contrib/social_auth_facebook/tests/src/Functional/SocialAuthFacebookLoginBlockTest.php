<?php

namespace Drupal\Tests\social_auth_facebook\Functional;

use Drupal\Tests\social_auth\Functional\SocialAuthTestBase;

/**
 * Test that path to authentication route exists in Social Auth Login block.
 *
 * @group social_auth
 *
 * @ingroup social_auth_facebook
 */
class SocialAuthFacebookLoginBlockTest extends SocialAuthTestBase {
<<<<<<< HEAD
=======

>>>>>>> 25e9ac38d (added login facebook)
  /**
   * Modules to enable.
   *
   * @var array
   */
<<<<<<< HEAD
  protected static $modules = ['block', 'social_auth_facebook'];
=======
  public static $modules = ['block', 'social_auth_facebook'];
>>>>>>> 25e9ac38d (added login facebook)

  /**
   * {@inheritdoc}
   */
<<<<<<< HEAD
  protected function setUp(): void {
=======
  protected function setUp() {
>>>>>>> 25e9ac38d (added login facebook)
    parent::setUp();

    $this->provider = 'facebook';
  }

  /**
   * Test that the path is included in the login block.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testLinkExistsInBlock() {
    $this->checkLinkToProviderExists();
  }

}
