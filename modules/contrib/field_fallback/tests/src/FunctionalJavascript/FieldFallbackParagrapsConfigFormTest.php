<?php

namespace Drupal\Tests\field_fallback\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;

/**
 * Test field_fallback in combination with paragraphs on field_config_form.
 *
 * @group field_fallback
 */
class FieldFallbackParagrapsConfigFormTest extends WebDriverTestBase {

  use ParagraphsTestBaseTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_fallback',
    'field_fallback_test',
    'entity_reference_revisions',
    'filter',
    'paragraphs',
    'paragraphs_summary_token',
    'block',
    'field_ui',
    'node',
    'taxonomy',
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

    // Create a content type with paragraphs.
    $this->addParagraphsType('text');
    $this->addFieldtoParagraphType('text', 'field_body', 'text_long');
    $this->addParagraphedContentType('page');

    // Create primary field.
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_summary',
      'type' => 'text_long',
      'cardinality' => 1,
      'translatable' => FALSE,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_summary',
      'bundle' => 'page',
      'label' => 'Summary',
    ])->save();

    // Add fields to the form display.
    EntityFormDisplay::load('node.page.default')
      ->setComponent('field_summary', ['type' => 'text_textarea'])
      ->save();

    // Add fields to the view display.
    EntityViewDisplay::load('node.page.default')
      ->setComponent('field_summary', ['region' => 'content'])
      ->save();

    $this->drupalLogin($this->rootUser);
  }

  /**
   * Test fallback fields with entity reference fields.
   */
  public function testConfigFormWithEntityReferenceRevisionsField() {
    $page = $this->getSession()->getPage();

    // Create an entity reference revisions field, referencing taxonomy terms.
    // No fallback fields should be available since there are no other entity
    // reference fields.
    $this->createEntityReferenceRevisionsField('node', 'page', 'field_err_primary', 'Entity Reference revisions Primary', 'taxonomy_term');
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_err_primary');
    $this->assertFalse($page->hasSelect('third_party_settings[field_fallback][field]'));

    // Create an entity reference revisions field, referencing nodes. No
    // fallback fields should be available since the target types don't match.
    $this->createEntityReferenceRevisionsField('node', 'page', 'field_err_node', 'Entity Reference Revisions Node', 'node');
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_err_primary');
    $this->assertFalse($page->hasSelect('third_party_settings[field_fallback][field]'));

    // Create an entity reference revisions field, referencing taxonomy terms.
    // Only field_err_secondary should be available since that field is
    // referencing the same entities.
    $this->createEntityReferenceRevisionsField('node', 'page', 'field_err_secondary', 'Entity Reference Revisions Secondary', 'taxonomy_term');
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_err_primary');
    $this->assertTrue($page->hasSelect('third_party_settings[field_fallback][field]'));
    $this->assertSession()->optionExists('third_party_settings[field_fallback][field]', 'field_err_secondary');
    $this->assertSession()->optionNotExists('third_party_settings[field_fallback][field]', 'field_err_node');
  }

  /**
   * Test the paragraphs summary field fallback plugin.
   */
  public function testParagraphsSummaryFieldFallback() {
    $this->createEntityReferenceRevisionsField('node', 'page', 'field_err_taxonomy', 'Entity Reference revisions taxonomy', 'taxonomy_term');

    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_summary');
    $field_fallback_field = $this->getSession()->getPage()->findField('third_party_settings[field_fallback][field]');
    // Check that the field_err_taxonomy field is not available since it's
    // referencing other entities.
    $this->assertSession()->optionNotExists('third_party_settings[field_fallback][field]', 'field_err_taxonomy');
    $field_fallback_field->selectOption('field_paragraphs');

    // Set the paragraphs summary as converter.
    $field_fallback_converter = $this->assertSession()->waitForField('third_party_settings[field_fallback][converter]');
    $field_fallback_converter->setValue('paragraphs_summary');
    $format_field = $this->assertSession()->waitForField('third_party_settings[field_fallback][configuration][format]');
    $format_field->setValue(('test_filter2'));
    $this->getSession()->getPage()->pressButton('Save');

    $content = $this->randomMachineName(350);
    $paragraph = Paragraph::create([
      'type' => 'text',
      'field_body' => '<ul><li>Test</li></ul><p>' . $content . '</p>',
    ]);
    $paragraph->save();

    // Add test content with paragraph container.
    $node = Node::create([
      'type' => 'page',
      'title' => 'Paragraphs Test',
      'field_paragraphs' => [$paragraph],
    ]);
    $node->save();

    $this->drupalGet($node->toUrl());
    $this->assertSession()->elementTextContains('css', '.field--name-field-summary .field__item', substr($content, 0, 200));
    $this->assertSession()->elementNotContains('css', '.field--name-field-summary .field__item', '<ul>');

    // Check that the summary field is empty.
    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertSession()->fieldValueEquals('field_summary[0][value]', '');
  }

  /**
   * Creates an entity reference revisions field.
   *
   * @param string $entity_type
   *   The type of entity the field will be attached to.
   * @param string $bundle
   *   The bundle name of the entity the field will be attached to.
   * @param string $field_name
   *   The name of the field.
   * @param string $field_label
   *   The label of the field.
   * @param string $target_entity_type
   *   The type of the referenced entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createEntityReferenceRevisionsField(string $entity_type, string $bundle, string $field_name, string $field_label, string $target_entity_type) {
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => 'entity_reference_revisions',
      'settings' => [
        'target_type' => $target_entity_type,
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $bundle,
      'translatable' => FALSE,
      'label' => $field_label,
    ]);
    $field->save();

    // Add field to the form display.
    EntityFormDisplay::load(sprintf('%s.%s.default', $entity_type, $bundle))
      ->setComponent($field_name, ['type' => 'text_textfield'])
      ->save();

    // Add field to the view display.
    EntityViewDisplay::load(sprintf('%s.%s.default', $entity_type, $bundle))
      ->setComponent($field_name, ['region' => 'content'])
      ->save();
  }

}
