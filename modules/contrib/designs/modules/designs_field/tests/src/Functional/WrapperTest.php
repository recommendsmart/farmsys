<?php

namespace Drupal\Tests\designs_field\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\designs\Traits\DesignsStandardTrait;
use Drupal\Tests\designs\Traits\DesignsTestTrait;

/**
 * Tests for the designs field wrapper.
 *
 * @group designs_field
 */
class WrapperTest extends BrowserTestBase {

  use DesignsTestTrait;
  use DesignsStandardTrait;

  /**
   * The theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * The modules.
   *
   * @var array
   */
  protected static $modules = [
    'designs',
    'designs_test',
    'designs_field',
    'node',
    'field',
    'field_ui',
  ];

  /**
   * The random node type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeType;

  /**
   * The field information.
   *
   * @var array
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->nodeType = $this->drupalCreateContentType();
    $type_id = $this->nodeType->id();

    // Create a user that can edit and view the content.
    $web_user = $this->drupalCreateUser([
      "access content",
      "administer nodes",
      "administer node fields",
      "administer node form display",
      "administer node display",
      "create {$type_id} content",
      "edit any {$type_id} content",
    ]);
    $this->drupalLogin($web_user);

    // Create the fields associated with the node type.
    $field_storage = [
      'field_name' => 'field_test',
      'entity_type' => 'node',
      'type' => 'boolean',
    ];
    FieldStorageConfig::create($field_storage)->save();

    $this->field = [
      'entity_type' => 'node',
      'bundle' => $type_id,
      'field_name' => $field_storage['field_name'],
      'label' => $this->randomMachineName() . '_label',
      'description' => '[site:name]_description',
      'settings' => [],
    ];
    FieldConfig::create($this->field)->save();
  }

  /**
   * Data provider for testWrapper.
   *
   * @return \string[][]
   */
  public function providerTestWrapper() {
    return [
      ['item', 'Item Design: @label', 'delta'],
      ['field', 'Field Design: @label', 'label'],
    ];
  }

  /**
   * Test the form wrapper behaviour.
   *
   * @dataProvider providerTestWrapper
   */
  public function testWrapper($wrapper_field, $wrapper_label, $wrapper_source) {
    $custom_id = strtolower($this->randomMachineName());
    $custom_label = $this->randomMachineName();
    $custom_text = $this->randomMachineName();
    $title_text = $this->randomMachineName();
    $attributes = "id=\"{$custom_id}\"";

    $type_id = $this->nodeType->id();
    $this->drupalGet("admin/structure/types/manage/{$type_id}/display");

    $field_name = $this->field['field_name'];

    $this->getSession()->getPage()->selectFieldOption("fields[{$field_name}][region]", 'content');
    $this->submitForm([], 'Save');
    $this->submitForm([], "{$field_name}_settings_edit");
    $this->submitForm([
      "fields[{$field_name}][settings_edit_form][third_party_settings][designs_field][{$wrapper_field}_enabled]" => "1"
    ], 'Save');
    $this->submitForm([], "{$field_name}_settings_edit");

    // Setup the wrapper.
    $parents = "fields[{$field_name}][settings_edit_form][third_party_settings][designs_field][{$wrapper_field}]";
    $this->drupalDesign(
      $parents,
      ['attributes' => $attributes],
      [
        'id' => $custom_id,
        'label' => $custom_label,
        'text' => $custom_text,
      ],
      [
        $wrapper_source,
        $custom_id,
        'content',
      ],
    );
    $this->drupalSetupDesignContent($parents, [
      'title' => [
        'plugin' => 'text',
        'config' => [
          'value' => $title_text,
        ],
      ],
    ]);
    $this->submitForm([
    ], 'Save');

    // Check the appropriate summary text is located.
    $this->assertSession()->pageTextContainsOnce(t($wrapper_label, ['@label' => 'Content']));

    // Check the wrapper has been saved with the field formatter third
    // party settings.
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $display = \Drupal::service('entity_display.repository')
      ->getViewDisplay($this->field['entity_type'], $this->field['bundle']);

    $design = $display->getRenderer($this->field['field_name'])->getThirdPartySettings('designs_field')[$wrapper_field];
    $this->assertIsArray($design);
    $this->assertEquals('content', $design['design']);
    $this->assertEquals($attributes, $design['settings']['attributes']['attributes']);
    $this->assertEquals('article', $design['settings']['tag']['value']);
    $this->assertEquals($custom_label, $design['content'][$custom_id]['config']['label']);
    $this->assertEquals($custom_text, $design['content'][$custom_id]['config']['value']);
    $this->assertEquals($title_text, $design['content']['title']['config']['value']);
    $this->assertTrue(in_array($wrapper_source, $design['regions']['content']));
    $this->assertTrue(in_array('content', $design['regions']['content']));
    $this->assertTrue(in_array($custom_id, $design['regions']['content']));
  }

}
