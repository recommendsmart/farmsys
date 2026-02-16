<?php

namespace Drupal\Tests\field_fallback\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\Tests\BrowserTestBase;

/**
 * Test field_fallback with multilingual entities.
 *
 * @group field_fallback
 */
class FieldFallbackMultilingualTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_fallback_test',
    'block',
    'content_translation',
    'language',
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
      'translatable' => TRUE,
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
      'translatable' => TRUE,
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

    // Add FR language.
    ConfigurableLanguage::createFromLangcode('fr')->save();

    // Add path_prefix based language negotiation.
    $this->config('language.negotiation')
      ->set('url.source', 'path_prefix')
      ->set('url.prefixes', ['en' => 'en', 'fr' => 'fr'])
      ->save();

    // Turn on content translation for test pages.
    $config = ContentLanguageSettings::loadByEntityTypeBundle('node', 'page');
    $config->setDefaultLangcode('en')
      ->setLanguageAlterable(TRUE)
      ->save();
  }

  /**
   * Test this module with translated entities.
   */
  public function testMultilingualFieldValues() {
    $primary_value = $this->randomMachineName();

    $node = $this->drupalCreateNode([
      'title' => $this->randomMachineName(),
    ]);

    $node->addTranslation('fr', [
      'title' => $this->randomMachineName(),
      'field_primary' => $primary_value,
    ]);
    $node->save();

    // The original entity doesn't have a primary value.
    $this->drupalGet('/node/' . $node->id());
    $this->assertSession()->elementNotExists('css', '.field--name-field-primary');
    $this->assertSession()->elementNotExists('css', '.field--name-field-secondary');

    // The translated entity has a primary value. Check that the fallback still
    // works.
    $this->drupalGet('/fr/node/' . $node->id());
    $this->assertSession()->elementTextContains('css', '.field--name-field-primary', $primary_value);
    $this->assertSession()->elementTextContains('css', '.field--name-field-secondary', $primary_value);
  }

}
