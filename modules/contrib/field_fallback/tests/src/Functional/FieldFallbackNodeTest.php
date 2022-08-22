<?php

namespace Drupal\Tests\field_fallback\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Test field_fallback on the node edit form.
 *
 * @group field_fallback
 */
class FieldFallbackNodeTest extends BrowserTestBase {

  use FieldUiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_fallback_test',
    'block',
    'field_ui',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalCreateContentType(['type' => 'page']);

    $this->drupalLogin($this->rootUser);

    // Create primary field.
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_primary',
      'type' => 'string',
      'cardinality' => 1,
      'translatable' => FALSE,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_primary',
      'bundle' => 'page',
      'label' => 'Primary',
    ])->save();

    // Create secondary field.
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_secondary',
      'type' => 'string',
      'cardinality' => 1,
      'translatable' => FALSE,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_secondary',
      'bundle' => 'page',
      'label' => 'Secondary',
      'widget' => [
        'type' => 'text_textfield',
        'weight' => 0,
      ],
      'third_party_settings' => [
        'field_fallback' => [
          'field' => 'field_primary',
          'converter' => 'default',
        ],
      ],
    ])->save();

    // Add fields to the form display.
    EntityFormDisplay::load('node.page.default')
      ->setComponent('field_primary', ['type' => 'text_textfield'])
      ->setComponent('field_secondary', ['type' => 'text_textfield'])
      ->save();

    // Add fields to the view display.
    EntityViewDisplay::load('node.page.default')
      ->setComponent('field_primary', ['region' => 'content'])
      ->setComponent('field_secondary', ['region' => 'content'])
      ->save();

    // This rebuild is necessary or the tests won't know about our newly
    // created fields.
    $this->rebuildAll();
  }

  /**
   * Test this module on the node edit form.
   */
  public function testNodeEditForm() {
    $this->drupalGet('node/add/page');
    $title = $this->randomMachineName();
    $primary_value = $this->randomMachineName();
    $secondary_value = $this->randomMachineName();
    $this->submitForm([
      'title[0][value]' => $title,
      'field_primary[0][value]' => $primary_value,
    ], 'Save');

    // Check if the value equals the value of the primary field.
    $node = $this->drupalGetNodeByTitle($title, TRUE);
    $this->assertEquals($primary_value, $node->get('field_secondary')->value);

    // Check if the secondary field is empty on the node edit form.
    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertSession()->fieldValueEquals('field_primary[0][value]', $primary_value);
    $this->assertSession()->fieldValueEquals('field_secondary[0][value]', '');

    // When filling the secondary field with the same value as the primary
    // field. The value should no longer be overridden.
    $this->submitForm([
      'field_secondary[0][value]' => $primary_value,
    ], 'Save');

    $node = $this->drupalGetNodeByTitle($title, TRUE);
    $this->assertEquals($primary_value, $node->get('field_secondary')->value);

    // Since the value of the secondary field was overridden, the value should
    // be shown on the node edit form.
    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertSession()->fieldValueEquals('field_primary[0][value]', $primary_value);
    $this->assertSession()->fieldValueEquals('field_secondary[0][value]', $primary_value);

    // When clearing the primary field, the secondary field should keep it's
    // value.
    $this->submitForm([
      'field_primary[0][value]' => NULL,
    ], 'Save');

    $node = $this->drupalGetNodeByTitle($title, TRUE);
    $this->assertEquals($primary_value, $node->get('field_secondary')->value);
    $this->assertTrue($node->get('field_primary')->isEmpty());

    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertSession()->fieldValueEquals('field_primary[0][value]', '');
    $this->assertSession()->fieldValueEquals('field_secondary[0][value]', $primary_value);

    // Assign values to both fields. They should have their own value.
    $this->submitForm([
      'field_primary[0][value]' => $primary_value,
      'field_secondary[0][value]' => $secondary_value,
    ], 'Save');

    $node = $this->drupalGetNodeByTitle($title, TRUE);
    $this->assertEquals($primary_value, $node->get('field_primary')->value);
    $this->assertEquals($secondary_value, $node->get('field_secondary')->value);

    // When clearing the secondary field, it should fallback again to the
    // primary field.
    $this->drupalGet($node->toUrl('edit-form'));
    $this->submitForm([
      'field_secondary[0][value]' => NULL,
    ], 'Save');

    $node = $this->drupalGetNodeByTitle($title, TRUE);
    $this->assertEquals($primary_value, $node->get('field_primary')->value);
    $this->assertEquals($primary_value, $node->get('field_secondary')->value);

    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertSession()->fieldValueEquals('field_primary[0][value]', $primary_value);
    $this->assertSession()->fieldValueEquals('field_secondary[0][value]', '');
  }

  /**
   * Test this module on the node page.
   */
  public function testNodeView() {
    $primary_value = $this->randomMachineName();
    $secondary_value = $this->randomMachineName();

    $node = $this->drupalCreateNode([
      'title' => $this->randomMachineName(),
      'field_primary' => $primary_value,
    ]);

    // Secondary field should fallback to the value of the primary field.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->elementTextContains('css', '.field--name-field-primary', $primary_value);
    $this->assertSession()->elementTextContains('css', '.field--name-field-secondary', $primary_value);

    $node->set('field_secondary', $secondary_value);
    $node->save();

    // Secondary field should show it's own value.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->elementTextContains('css', '.field--name-field-primary', $primary_value);
    $this->assertSession()->elementTextContains('css', '.field--name-field-secondary', $secondary_value);

    $node->set('field_secondary', NULL);
    $node->save();

    // Secondary field should fallback again to the value of the primary field.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->elementTextContains('css', '.field--name-field-primary', $primary_value);
    $this->assertSession()->elementTextContains('css', '.field--name-field-secondary', $primary_value);
  }

  /**
   * Test this module with a custom converter.
   */
  public function testNodeFieldWithConverter() {
    // Assign the static string converter, which will return a static dummy
    // value.
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_secondary');
    $this->submitForm(['third_party_settings[field_fallback][converter]' => 'static_string'], 'Save settings');

    $node = $this->drupalCreateNode([
      'title' => $this->randomMachineName(),
      'field_primary' => $this->randomMachineName(),
    ]);

    $this->drupalGet($node->toUrl());
    $this->assertSession()->elementTextContains('css', '.field--name-field-secondary', 'Test value');
  }

  /**
   * Test that the fallback field doesn't exceed the configured cardinality.
   */
  public function testCardinalityFieldValue() {
    // Set an unlimited cardinality for the primary field.
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_primary/storage');
    $this->submitForm(['cardinality' => -1], 'Save field settings');

    // Allow max. 3 values for the secondary field.
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_secondary/storage');
    $this->submitForm(['cardinality_number' => 3], 'Save field settings');

    $node = $this->drupalCreateNode([
      'title' => $this->randomMachineName(),
      'field_primary' => [
        'first_value',
        'second_value',
        'third_value',
      ],
    ]);

    // Check that the 3 values are shown.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->elementTextContains('css', '.field--name-field-secondary', 'first_value');
    $this->assertSession()->elementTextContains('css', '.field--name-field-secondary', 'second_value');
    $this->assertSession()->elementTextContains('css', '.field--name-field-secondary', 'third_value');

    // Update the cardinality to 2.
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_secondary/storage');
    $this->submitForm(['cardinality_number' => 2], 'Save field settings');

    // Check that only the first 2 values are shown.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->elementTextContains('css', '.field--name-field-secondary', 'first_value');
    $this->assertSession()->elementTextContains('css', '.field--name-field-secondary', 'second_value');
    $this->assertSession()->elementTextNotContains('css', '.field--name-field-secondary', 'third_value');

    // Update the cardinality to 1.
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_secondary/storage');
    $this->submitForm(['cardinality_number' => 1], 'Save field settings');

    // Check that only the first value is shown.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->elementTextContains('css', '.field--name-field-secondary', 'first_value');
    $this->assertSession()->elementTextNotContains('css', '.field--name-field-secondary', 'second_value');
    $this->assertSession()->elementTextNotContains('css', '.field--name-field-secondary', 'third_value');
  }

}
