<?php

namespace Drupal\Tests\number_formatter\Kernel\Formatter;

use Drupal\KernelTests\KernelTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\number_formatter\Plugin\Field\FieldFormatter\NumberFormatter;
use NumberFormatter as IntlNumberFormatter;

/**
 * Tests the number_formatter.
 *
 * @group number_formatter
 */
class NumberFormatterTest extends KernelTestBase {

  /**
   * Modules the tests depend on.
   *
   * @var array
   */
  public static $modules = [
    'entity_test',
    'field',
    'language',
    'number_formatter',
    'system',
    'user',
  ];

  /**
   * The generated field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['number_formatter']);
    $this->installEntitySchema('entity_test');

    $this->fieldName = mb_strtolower($this->randomMachineName());

    $field_storage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'entity_test',
      'type' => 'decimal',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
      'label' => $this->randomMachineName(),
    ]);
    $field->save();
  }

  /**
   * Tests decimal formatting.
   */
  public function testDecimal() {
    $entity = EntityTest::create([]);
    $entity->{$this->fieldName} = [
      'value' => 1000000,
    ];
    $result = $entity->{$this->fieldName}->view([
      'type' => 'number_formatter',
      'settings' => [
        'style' => IntlNumberFormatter::DECIMAL,
        'lang_select' => NumberFormatter::LANGUAGE_SELECT_FIELD,
      ],
    ]);

    $this->assertEquals('1,000,000', $result[0]['#markup']);
  }

  /**
   * Tests USD currency formatting.
   */
  public function testCurrencyUsd() {
    $entity = EntityTest::create([]);
    $entity->{$this->fieldName} = [
      'value' => 200000,
    ];
    $result = $entity->{$this->fieldName}->view([
      'type' => 'number_formatter',
      'settings' => [
        'style' => IntlNumberFormatter::CURRENCY,
        'currency' => 'USD',
        'lang_select' => NumberFormatter::LANGUAGE_SELECT_FIELD,
      ],
    ]);

    $this->assertEquals('$200,000.00', $result[0]['#markup']);
  }

  /**
   * Tests DKK currency formatting.
   */
  public function testCurrencyDkk() {
    $entity = EntityTest::create([]);
    $entity->{$this->fieldName} = [
      'value' => 200000,
    ];

    $result = $entity->{$this->fieldName}->view([
      'type' => 'number_formatter',
      'settings' => [
        'style' => IntlNumberFormatter::CURRENCY,
        'currency' => 'DKK',
        'lang_select' => NumberFormatter::LANGUAGE_SELECT_CURRENT,
      ],
    ]);

    $this->assertEquals('DKK200,000.00', $result[0]['#markup']);
  }

}
