<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for excluded elements element.
 *
 * @group webform
 */
class WebformElementExcludedElementsTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_excluded_elements'];

  /**
   * Test excluded elements element.
   */
  public function testExcludedElements() {
    $this->drupalGet('/webform/test_element_excluded_elements');

    // Check markup is not listed via '#exclude_markup': TRUE.
    $this->assertNoFieldByName('webform_excluded_elements[tableselect][markup]');

    // Check markup is listed via '#exclude_markup': FALSE.
    $this->assertFieldByName('webform_excluded_elements_markup[tableselect][markup]');

    // Check composite sub element is listed via '#exclude_composite': TRUE.
    $this->assertNoFieldByName('webform_excluded_elements[tableselect][telephone__type]');

    // Check composite sub element is listed via '#exclude_composite': FALSE.
    $this->assertFieldByName('webform_excluded_elements_telephone[tableselect][telephone__type]');

    // Check composite sub element title is prepended with the element's title.
    $this->assertRaw('<td>Type</td>');
  }

}
