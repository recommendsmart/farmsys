<?php

namespace Drupal\Tests\designs_layout\Functional;

use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\designs\Traits\DesignsStandardTrait;
use Drupal\Tests\designs\Traits\DesignsTestTrait;

/**
 * Tests the Layout Builder UI for designs.
 *
 * @group designs_layout
 */
class LayoutBuilderTest extends BrowserTestBase {

  use DesignsTestTrait;
  use DesignsStandardTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder',
    'block',
    'node',
    'designs',
    'designs_layout',
    'designs_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');

    // Create two nodes.
    $this->createContentType([
      'type' => 'bundle_with_section_field',
      'name' => 'Bundle with section field',
    ]);
    $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The first node title',
      'body' => [
        [
          'value' => 'The first node body',
        ],
      ],
    ]);
    $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The second node title',
      'body' => [
        [
          'value' => 'The second node body',
        ],
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function testLayoutBuilderUi() {
    $custom_id = strtolower($this->randomMachineName());
    $attributes = "id=\"{$custom_id}\"";

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer node fields',
    ]));

    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';

    // From the manage display page, go to manage the layout.
    $this->drupalGet("$field_ui_prefix/display/default");
    $assert_session->linkNotExists('Manage layout');
    $assert_session->fieldDisabled('layout[allow_custom]');

    $this->submitForm(['layout[enabled]' => TRUE], 'Save');
    $assert_session->linkExists('Manage layout');
    $this->clickLink('Manage layout');

    // Remove the existing section content.
    $assert_session->linkExists('Remove Section 1');
    $this->clickLink('Remove Section 1');
    $page->pressButton('Remove');

    // Add a new section.
    $this->clickLink('Add section');
    $assert_session->linkExists('Content');
    $this->clickLink('Content');

    $parents = "layout_options";
    $this->drupalSetupDesignSettings($parents, [
      'attributes' => [
        'attributes' => $attributes,
      ],
      'tag' => [
        'value' => 'article',
      ],
    ]);

    $assert_session->buttonExists('Add section');
    $page->pressButton('Add section');
    $page->pressButton('Save');

    // Get the layout storage and check the design has been properly set.
    $display = LayoutBuilderEntityViewDisplay::load("node.bundle_with_section_field.default");
    $section = $display->getSection(0);
    $config = $section->getLayoutSettings();
    $this->assertEquals('design:content', $section->getLayoutId());
    $this->assertEquals($attributes, $config['settings']['attributes']['attributes']);
    $this->assertEquals('article', $config['settings']['tag']['value']);
  }

}
