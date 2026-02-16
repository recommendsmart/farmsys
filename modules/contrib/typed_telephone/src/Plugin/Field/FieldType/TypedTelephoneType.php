<?php

namespace Drupal\typed_telephone\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'typed_telephone' field type.
 *
 * @FieldType(
 *   id = "typed_telephone",
 *   label = @Translation("Typed telephone"),
 *   description = @Translation("Typed telephone"),
 *   default_widget = "typed_telephone_default_widget",
 *   default_formatter = "typed_telephone_default_formatter"
 * )
 */
class TypedTelephoneType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'allowed_types' => '',
      'max_length' => 255,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['teltype'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Telephone Type'))
      ->setRequired(TRUE);

    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Telephone value'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'teltype' => [
          'type' => 'varchar',
          'length' => 30,
        ],
        'value' => [
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    if ($max_length = $this->getSetting('max_length')) {
      $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
      $constraints[] = $constraint_manager->create('ComplexData', [
        'teltype' => [
          'Length' => [
            'max' => 30,
            'maxMessage' => $this->t('%name: type may not be longer than 30 characters.', [
              '%name' => $this->getFieldDefinition()->getLabel(),
            ]),
          ],
        ],
        'value' => [
          'Length' => [
            'max' => $max_length,
            'maxMessage' => $this->t('%name: telephone may not be longer than @max characters.', [
              '%name' => $this->getFieldDefinition()->getLabel(),
              '@max' => $max_length,
            ]),
          ],
        ],
      ]);
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['value'] = $random->word(mt_rand(1, $field_definition->getSetting('max_length')));
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];
    // Is it even possible to inject ConfigManager here?
    $config_helper = \Drupal::service('typed_telephone.confighelper');

    $elements['allowed_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed telephone types'),
      '#default_value' => $this->getSetting('allowed_types')??$config_helper->getTypesAsOptions(),
      '#required' => TRUE,
      '#description' => $this->t('The allowed telephone types for this field.'),
      '#options' => $config_helper->getTypesAsOptions(),
      '#disabled' => $has_data,
    ];

    $elements['max_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum length'),
      '#default_value' => $this->getSetting('max_length'),
      '#required' => TRUE,
      '#description' => $this->t('The maximum length of the field in characters.'),
      '#min' => 1,
      '#disabled' => $has_data,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
