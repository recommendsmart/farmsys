<?php

namespace Drupal\Tests\freelinking\Functional;

/**
 * Tests that text is displayed inline after rendering.
 *
 * @group freelinking
 */
class FreelinkingPrefixAffixTest extends FreelinkingBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Make sure that freelinking filter is activated.
    $this->updateFilterSettings();
  }

  /**
   * Asserts that text does not contain a break before or after the text.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testPrefixAffix() {
    // Create node that will contain a sample of each plugin.
    $edit = [];
    $edit['title[0][value]'] = $this->getRandomGenerator()->sentences(2);
    $edit['body[0][value]'] = $this->getNodeBodyValue();

    $this->drupalPostForm('node/add/page', $edit, t('Save'));
    $this->assertSession()
      ->pageTextContains(t('Basic page @title has been created.', ['@title' => $edit['title[0][value]']]));

    // Confirm that the text is inline.
    $this->assertSession()
      ->pageTextContains('PrefixFirst pageSuffix');
  }

  /**
   * {@inheritdoc}
   */
  protected function getNodeBodyValue() {
    return "Prefix[[First page]]Suffix";
  }

}
