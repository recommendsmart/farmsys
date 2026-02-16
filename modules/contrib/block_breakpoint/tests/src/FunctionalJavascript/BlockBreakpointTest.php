<?php

namespace Drupal\Tests\block_breakpoint\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\field_group\Functional\FieldGroupTestTrait;

/**
 * Tests the Block breakpoint module functional.
 *
 * @group block_breakpoint
 */
class BlockBreakpointTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block_breakpoint',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permissions to configure blocks.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $blockEditUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Log in as a content author.
    $this->blockEditUser = $this->drupalCreateUser([
      'administer blocks',
    ]);
    $this->drupalLogin($this->blockEditUser);

    // Place the powered by block.
    $this->drupalPlaceBlock('system_powered_by_block', ['id' => 'powered_by']);
  }

  /**
   * Test that selection of a breakpoint group results in correct breakpoints.
   */
  public function testBreakpointSelection() {
    // Open the block edit page.
    $this->drupalGet('/admin/structure/block/manage/powered_by');
    $this->getSession()->getPage()->checkField('block_breakpoint[enabled]');

    // Select the stark breakpoint group.
    $this->getSession()->getPage()->selectFieldOption('block_breakpoint[breakpoint_group]', 'stark');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->optionExists('block_breakpoint[breakpoints][]', 'stark.mobile');
    $this->assertSession()->optionExists('block_breakpoint[breakpoints][]', 'stark.narrow');
    $this->assertSession()->optionExists('block_breakpoint[breakpoints][]', 'stark.wide');
  }

  /**
   * Test that selection block breakpoints make blocks visible and invisible.
   */
  public function testBreakpoint() {
    // Visit the frontpage. We should see the block.
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains('Powered by Drupal');

    // Open the block edit page.
    $this->drupalGet('/admin/structure/block/manage/powered_by');
    $this->getSession()->getPage()->checkField('block_breakpoint[enabled]');

    // Select the stark narrow breakpoints.
    $this->getSession()->getPage()->selectFieldOption('block_breakpoint[breakpoint_group]', 'stark');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->selectFieldOption('block_breakpoint[breakpoints][]', 'stark.narrow', TRUE);

    // Save the block.
    $this->getSession()->getPage()->pressButton('edit-actions-submit');

    // Visit the frontpage. We should not see the block anymore.
    $this->drupalGet('<front>');
    $this->assertSession()->elementNotExists('css', '#block-powered-by');
    $this->assertSession()->pageTextNotContains('Powered by Drupal');

    // Select the stark mobile and wide breakpoints.
    $this->drupalGet('/admin/structure/block/manage/powered_by');
    $this->assertSession()->fieldValueEquals('block_breakpoint[breakpoint_group]', 'stark');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->selectFieldOption('block_breakpoint[breakpoints][]', 'stark.mobile');
    $this->getSession()->getPage()->selectFieldOption('block_breakpoint[breakpoints][]', 'stark.wide', TRUE);

    // Save the block.
    $this->getSession()->getPage()->pressButton('edit-actions-submit');

    // Visit the frontpage. We should see the block.
    $this->drupalGet('<front>');
    $block = $this->assertSession()->elementExists('css', '#block-powered-by');
    $this->assertTrue($block->hasClass('block-breakpoint'), 'The element should have the block-breakpoint class.');
    $this->assertTrue($block->hasAttribute('data-block-breakpoint-media-query'), 'The element should have the data-block-breakpoint-media-query attribute.');
    $this->assertSession()->pageTextContains('Powered by Drupal');
  }

}
