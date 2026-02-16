<?php

namespace Drupal\Tests\freelinking\Functional;

/**
 * Tests the "showtext" builtin failover plugin with node title.
 *
 * @group freelinking
 */
class NodeTitleBuiltinFailoverTest extends FreelinkingBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $edit = [
      'filters[freelinking][status]' => 1,
      'filters[freelinking][weight]' => 0,
      'filters[freelinking][settings][plugins][nodetitle][enabled]' => 1,
      'filters[freelinking][settings][plugins][nodetitle][settings][failover]' => 'showtext',
      'filters[filter_url][weight]' => 1,
      'filters[filter_html][weight]' => 2,
      'filters[filter_autop][weight]' => 3,
      'filters[filter_htmlcorrector][weight]' => 4,
    ];
    $this->updateFilterSettings('plain_text', $edit);
  }

  /**
   * Asserts that showtext failover option is functional.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testNodeTitleShowtextFailover() {
    // Create node that will contain a sample of each plugin.
    $edit = [];
    $edit['title[0][value]'] = t('Testing all freelinking plugins');
    $edit['body[0][value]'] = $this->getNodeBodyValue();

    $this->drupalPostForm('node/add/page', $edit, t('Save'));
    $this->assertSession()
      ->pageTextContains(t('Basic page @title has been created.', ['@title' => $edit['title[0][value]']]));

    // Verify each freelink plugin.
    $this->assertSession()
      ->linkNotExists('Third page', 'No link exists for the third page.');
    $this->assertSession()
      ->pageTextContainsOnce('Third page');
  }

  /**
   * Get HTML to use for node body.
   *
   * @return string
   *   The value to use for the node body.
   */
  protected function getNodeBodyValue() {
    return <<<EOF
      <ul>
        <li>Nodetitle:      [[nodetitle:First page]]</li>
        <li>Nodetitle:      [[nodetitle:Second page]]</li>
        <li>Nodetitle:      [[nodetitle:Third page]]</li>
      </ul>
EOF;
  }

}
