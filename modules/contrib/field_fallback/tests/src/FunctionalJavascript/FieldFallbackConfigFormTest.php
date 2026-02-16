<?php

namespace Drupal\Tests\field_fallback\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;

/**
 * Test field_fallback on field_config_form.
 *
 * @group field_fallback
 */
class FieldFallbackConfigFormTest extends WebDriverTestBase {

  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_fallback',
    'field_fallback_test',
    'block',
    'field_ui',
    'node',
    'taxonomy',
    'views',
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
  }

  /**
   * Test the field config form.
   */
  public function testConfigForm() {
    $page = $this->getSession()->getPage();
    // Add a new field.
    $this->createTextField('field_secondary', 'Secondary');
    // Alter the newly added field.
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_secondary');
    // The fallback field is only shown when relevant fields are available.
    $page->hasSelect('third_party_settings[field_fallback][field]');
    // Add a new field of the same type.
    $this->createTextField('field_primary', 'Primary');

    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_secondary');
    // Fallback field is now shown since a field of the same type was added.
    $this->assertSession()->optionExists('third_party_settings[field_fallback][field]', 'field_primary');
    $field_fallback_field = $page->findField('third_party_settings[field_fallback][field]');
    // Coupling the same field should not be possible.
    $this->assertSession()->optionNotExists('third_party_settings[field_fallback][field]', 'field_secondary');

    $field_fallback_field->selectOption('field_primary');
    $field_fallback_converter = $this->assertSession()->waitForField('third_party_settings[field_fallback][converter]');
    $field_fallback_converter->selectOption('default');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementNotExists('css', '#edit-field-fallback-configuration');
    $page->pressButton('Save');

    // Check if the fallback field is correctly saved in the field config.
    $fallback_field = $this->config('field.field.node.page.field_secondary')
      ->get('third_party_settings.field_fallback');
    $this->assertEquals([
      'field' => 'field_primary',
      'converter' => 'default',
    ], $fallback_field);

    // Add a third text field.
    $this->createTextField('field_tertiary', 'Tertiary');

    // Check that configuring an infinite loop of fallback fields is not
    // possible.
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_primary');
    $this->assertSession()->optionExists('third_party_settings[field_fallback][field]', 'field_tertiary');
    $this->assertSession()->optionNotExists('third_party_settings[field_fallback][field]', 'field_secondary');
    $this->assertSession()->optionNotExists('third_party_settings[field_fallback][field]', 'field_primary');

    // Chaining fields e.g. field_primary -> field_secondary -> field_tertiary
    // is not possible.
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_tertiary');
    $this->assertSession()->optionExists('third_party_settings[field_fallback][field]', 'field_primary');
    $this->assertSession()->optionNotExists('third_party_settings[field_fallback][field]', 'field_secondary');

    // Remove the fallback field.
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_secondary');
    $field_fallback_field = $page->findField('third_party_settings[field_fallback][field]');
    $field_fallback_field->selectOption('');
    $this->assertSession()->waitForElementRemoved('named', [
      'field',
      'third_party_settings[field_fallback][converter]',
    ]);
    $page->pressButton('Save');

    // Check if the fallback field is correctly removed from the config.
    $fallback_field = $this->config('field.field.node.page.field_secondary')
      ->get('third_party_settings.field_fallback');
    $this->assertEmpty($fallback_field);
  }

  /**
   * Test fallback fields with entity reference fields.
   */
  public function testConfigFormWithEntityReferenceField() {
    $page = $this->getSession()->getPage();

    // Create an entity reference field, referencing taxonomy terms. No
    // fallback fields should be available since there are no other entity
    // reference fields.
    $this->createEntityReferenceField('node', 'page', 'field_er_primary', 'Entity Reference Primary', 'taxonomy_term');
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_er_primary');
    $this->assertFalse($page->hasSelect('third_party_settings[field_fallback][field]'));

    // Create an entity reference field, referencing nodes. No fallback fields
    // should be available since the target types don't match.
    $this->createEntityReferenceField('node', 'page', 'field_er_node', 'Entity Reference Node', 'node');
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_er_primary');
    $this->assertFalse($page->hasSelect('third_party_settings[field_fallback][field]'));

    // Create an entity reference field, referencing taxonomy terms. Only
    // field_er_secondary should be available since that field is referencing
    // the same entities.
    $this->createEntityReferenceField('node', 'page', 'field_er_secondary', 'Entity Reference Secondary', 'taxonomy_term');
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_er_primary');
    $this->assertTrue($page->hasSelect('third_party_settings[field_fallback][field]'));
    $this->assertSession()->optionExists('third_party_settings[field_fallback][field]', 'field_er_secondary');
    $this->assertSession()->optionNotExists('third_party_settings[field_fallback][field]', 'field_er_node');

    $settings_handler_field = $this->getSession()->getPage()->findField('settings[handler]');
    $settings_handler_field->selectOption('views');

    $view_and_display_field = $this->assertSession()->waitForField('settings[handler_settings][view][view_and_display]');
    $view_and_display_field->selectOption('field_fallback_test_entity_reference_taxonomy:entity_reference_1');
    $field_fallback_field = $this->getSession()->getPage()->findField('third_party_settings[field_fallback][field]');
    $field_fallback_field->selectOption('field_er_secondary');
    $this->getSession()->getPage()->pressButton('Save');

    $this->assertSession()->pageTextNotContains('Vocabulary field is required.');
    /** @var \Drupal\field\FieldConfigInterface $field_er_secondary */
    $field_er_primary = FieldConfig::loadByName('node', 'page', 'field_er_primary');
    $field_er_primary_settings = $field_er_primary->getSettings();
    $this->assertEquals('views', $field_er_primary_settings['handler']);
    $this->assertEquals([
      'view' => [
        'view_name' => 'field_fallback_test_entity_reference_taxonomy',
        'display_name' => 'entity_reference_1',
        'arguments' => [],
      ],
    ], $field_er_primary_settings['handler_settings']);

    $field_er_primary_third_party_settings = $field_er_primary->getThirdPartySettings('field_fallback');
    $this->assertEquals([
      'field' => 'field_er_secondary',
      'converter' => 'default',
    ], $field_er_primary_third_party_settings);
  }

  /**
   * Test the configuration form with a configurable plugin.
   */
  public function testConfigFormWithPluginConfiguration() {
    // Add a new field.
    $this->createTextField('field_secondary', 'Secondary');
    // Add a new field of the same type.
    $this->createTextField('field_primary', 'Primary');

    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_secondary');
    $field_fallback_field = $this->getSession()->getPage()->findField('third_party_settings[field_fallback][field]');
    $field_fallback_field->selectOption('field_primary');
    $field_fallback_converter = $this->assertSession()->waitForField('third_party_settings[field_fallback][converter]');
    $field_fallback_converter->selectOption('static_string');

    // Make sure the plugin validation runs.
    $static_string_value_field = $this->assertSession()->waitForField('third_party_settings[field_fallback][configuration][static_string_value]');
    $static_string_value_field->setValue('fail');
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->responseContains('Value should not be fail.');

    // Save the new value.
    $static_string_value_field->setValue('New value');
    $this->getSession()->getPage()->pressButton('Save');

    // Check if the values are correctly saved in the config.
    $fallback_field = $this->config('field.field.node.page.field_secondary')
      ->get('third_party_settings.field_fallback');
    $this->assertEquals([
      'field' => 'field_primary',
      'converter' => 'static_string',
      'configuration' => [
        'static_string_value' => 'New value',
      ],
    ], $fallback_field);

    // Make sure the saved values are used as defaults.
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_secondary');
    $field_fallback_field = $this->assertSession()->fieldExists('third_party_settings[field_fallback][field]');
    $this->assertEquals('field_primary', $field_fallback_field->getValue());
    $field_fallback_converter = $this->assertSession()->fieldExists('third_party_settings[field_fallback][converter]');
    $this->assertEquals('static_string', $field_fallback_converter->getValue());
    $static_string_value_field = $this->assertSession()->fieldExists('third_party_settings[field_fallback][configuration][static_string_value]');
    $this->assertEquals('New value', $static_string_value_field->getValue());

    $node = $this->drupalCreateNode(['field_primary' => 'Test']);
    $this->drupalGet($node->toUrl());
    $this->assertSession()->elementTextContains('css', '.field--name-field-secondary .field__item', 'New value');
  }

  /**
   * Test the config form with a base field.
   */
  public function testConfigFormWithBaseField() {
    // Add a new field.
    $this->createTextField('field_title_fallback', 'Primary');

    // Configure the title as fallback value.
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_title_fallback');
    $field_fallback_field = $this->getSession()->getPage()->findField('third_party_settings[field_fallback][field]');
    $field_fallback_field->selectOption('title');
    $field_fallback_converter = $this->assertSession()->waitForField('third_party_settings[field_fallback][converter]');
    $field_fallback_converter->selectOption('default');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->pressButton('Save');

    // Check if the values are correctly saved in the config.
    $fallback_field = $this->config('field.field.node.page.field_title_fallback')
      ->get('third_party_settings.field_fallback');
    $this->assertEquals([
      'field' => 'title',
      'converter' => 'default',
    ], $fallback_field);

    // Check that the value of the field field_title_fallback matches the node
    // title.
    $node = $this->drupalCreateNode();
    $this->drupalGet($node->toUrl());
    $this->assertSession()->elementTextContains('css', '.field--name-field-title-fallback', $node->label());
  }

  /**
   * Helper method that creates a text field.
   *
   * @param string $machine_name
   *   The field machine name.
   * @param string $label
   *   The label.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createTextField(string $machine_name, string $label): void {
    // Create primary field.
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => $machine_name,
      'type' => 'string',
      'cardinality' => 1,
      'translatable' => FALSE,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => $machine_name,
      'bundle' => 'page',
      'label' => $label,
    ])->save();

    // Add fields to the form display.
    EntityFormDisplay::load('node.page.default')
      ->setComponent($machine_name, ['type' => 'text_textfield'])
      ->save();

    // Add fields to the view display.
    EntityViewDisplay::load('node.page.default')
      ->setComponent($machine_name, ['region' => 'content'])
      ->save();
  }

}
