<?php

namespace Drupal\field_fallback\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\FieldConfigInterface;
use Drupal\field_fallback\Plugin\FieldFallbackConverterManagerInterface;

/**
 * Helper class that alters the form 'field_config_edit_form'.
 */
class FieldFallbackFieldConfigForm {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The field config storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fieldConfigStorage;

  /**
   * The field fallback converter manager.
   *
   * @var \Drupal\field_fallback\Plugin\FieldFallbackConverterManagerInterface
   */
  protected $fieldFallbackConverterManager;

  /**
   * FieldConfigForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\field_fallback\Plugin\FieldFallbackConverterManagerInterface $field_fallback_converter_manager
   *   The field fallback converter manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager, FieldFallbackConverterManagerInterface $field_fallback_converter_manager) {
    $this->entityFieldManager = $entity_field_manager;
    $this->fieldConfigStorage = $entity_type_manager->getStorage('field_config');
    $this->fieldFallbackConverterManager = $field_fallback_converter_manager;
  }

  /**
   * Alter the form 'field_config_edit_form'.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function alterForm(array &$form, FormStateInterface $form_state): void {
    $form_object = $form_state->getFormObject();
    if (!$form_object instanceof EntityFormInterface) {
      return;
    }

    /** @var \Drupal\field\Entity\FieldConfig $field_config */
    $field_config = $form_object->getEntity();
    $settings = $field_config->getThirdPartySettings('field_fallback');

    $fallback_field_options = $this->buildFallbackFieldOptions($field_config);

    if (empty($fallback_field_options)) {
      return;
    }

    // Prepare group with fields for settings.
    $form['third_party_settings']['field_fallback'] = [
      '#type' => 'details',
      '#title' => $this->t('Configure fallback value'),
      '#description' => $this->t('When no value is provided, this field will fallback to the value of another configured field.'),
      '#open' => TRUE,
      '#weight' => 20,
      '#id' => 'field-fallback-wrapper',
    ];

    $form['third_party_settings']['field_fallback']['field'] = [
      '#type' => 'select',
      '#title' => $this->t('Fallback field'),
      '#default_value' => $settings['field'] ?? FALSE,
      '#empty_option' => $this->t('No fallback'),
      '#empty_value' => NULL,
      '#options' => $fallback_field_options,
      '#ajax' => [
        'event' => 'change',
        'wrapper' => 'field-fallback-wrapper',
        'callback' => [$this, 'updateFieldFallbackElement'],
      ],
    ];

    $user_input = $form_state->getUserInput();
    $field_value = isset($user_input['third_party_settings']['field_fallback']['field']) ? $user_input['third_party_settings']['field_fallback']['field'] : ($settings['field'] ?? NULL);

    if (!empty($field_value)) {
      $converter_options = $this->buildConverterOptions($field_config, $field_value);

      if (!empty($converter_options)) {
        $default_converter = $settings['converter'] ?? NULL;
        if ($default_converter === NULL) {
          $default_converter = count($converter_options) === 1 ? key($converter_options) : NULL;
        }

        $form['third_party_settings']['field_fallback']['converter'] = [
          '#type' => 'select',
          '#title' => $this->t('Converter'),
          '#default_value' => $default_converter,
          '#empty_option' => $this->t('- Choose -'),
          '#options' => $converter_options,
          '#required' => TRUE,
          '#ajax' => [
            'event' => 'change',
            'wrapper' => 'field-fallback-wrapper',
            'callback' => [$this, 'updateFieldFallbackElement'],
          ],
        ];
      }
    }

    $form['third_party_settings']['field_fallback']['configuration'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Converter configuration'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#weight' => 20,
      '#access' => FALSE,
    ];

    $converter_value = (string) ($user_input['third_party_settings']['field_fallback']['converter'] ?? ($settings['converter'] ?? ''));

    if (!empty($converter_value) && $this->fieldFallbackConverterManager->hasDefinition($converter_value)) {
      /** @var \Drupal\field_fallback\Plugin\FieldFallbackConverterInterface $plugin_instance */
      $plugin_instance = $this->fieldFallbackConverterManager->createInstance($converter_value, $settings['configuration'] ?? []);
      $form['third_party_settings']['field_fallback']['configuration'] = array_merge($form['third_party_settings']['field_fallback']['configuration'], $plugin_instance->buildConfigurationForm([], $form_state));
      $form['third_party_settings']['field_fallback']['configuration']['#access'] = !empty(Element::children($form['third_party_settings']['field_fallback']['configuration']));
    }

    $form['#validate'][] = [$this, 'validateForm'];
  }

  /**
   * Validate the field fallback value settings.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $fallback_converter = $form_state->getValue([
      'third_party_settings',
      'field_fallback',
      'converter',
    ]);
    $values = $form_state->getValues();
    if (!empty($fallback_converter) && is_string($fallback_converter) && $this->fieldFallbackConverterManager->hasDefinition($fallback_converter)) {
      /** @var \Drupal\field_fallback\Plugin\FieldFallbackConverterInterface $plugin_instance */
      $plugin_instance = $this->fieldFallbackConverterManager->createInstance($fallback_converter);
      $new_form_state_values = $form_state->getValue([
        'third_party_settings',
        'field_fallback',
        'configuration',
      ]);

      $configuration_form_state = (new FormState())->setValues(is_array($new_form_state_values) ? $new_form_state_values : []);
      $plugin_instance->validateConfigurationForm($form['third_party_settings']['field_fallback']['configuration'], $configuration_form_state);

      // Pass along errors from the plugin validation.
      foreach ($configuration_form_state->getErrors() as $key => $error) {
        $parents = implode('][', $form['third_party_settings']['field_fallback']['configuration']['#parents']);
        // If the plugin form used setError() then the parents will already be
        // part of the key since we are passing along the element in the context
        // of the whole form. If the plugin form used setErrorByName we need to
        // add the parents in.
        if (strpos($key, $parents) === FALSE) {
          $key = sprintf('%s][%s', $parents, $key);
        }
        $form_state->setErrorByName($key, $error);
      }

      NestedArray::setValue($values, $form['third_party_settings']['field_fallback']['configuration']['#parents'], $configuration_form_state->getValues());
      $form_state->setValues($values);
    }
    else {
      // Clear all configuration settings.
      NestedArray::setValue($values, $form['third_party_settings']['field_fallback']['configuration']['#parents'], []);
      $form_state->setValues($values);
    }

    $fallback_field = $form_state->getValue([
      'third_party_settings',
      'field_fallback',
      'field',
    ]);

    // When no fallback field is configured, clear all values so there are no
    // unused records saved in the field config.
    if (empty($fallback_field)) {
      $form_state->unsetValue(['third_party_settings', 'field_fallback']);
    }

    $fallback_converter_configuration = $form_state->getValue([
      'third_party_settings',
      'field_fallback',
      'configuration',
    ]);

    // Remove configuration from the form state when no values are configured.
    if (empty($fallback_converter_configuration)) {
      $form_state->unsetValue([
        'third_party_settings',
        'field_fallback',
        'configuration',
      ]);
    }
  }

  /**
   * Updates the field_fallback element after an ajax update.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The updated form element.
   */
  public static function updateFieldFallbackElement(array &$form, FormStateInterface $form_state): array {
    return $form['third_party_settings']['field_fallback'];
  }

  /**
   * Helper method that builds an option array for the fallback field.
   *
   * @param \Drupal\field\FieldConfigInterface $configured_field
   *   The currently configured field.
   *
   * @return array
   *   An option array containing field configs.
   */
  protected function buildFallbackFieldOptions(FieldConfigInterface $configured_field): array {
    $options = [];
    $source_field_types = $this->fieldFallbackConverterManager->getAvailableSourcesByTarget($configured_field->getType());
    $source_field_types[] = $configured_field->getType();
    foreach ($source_field_types as $source_field_type) {
      /** @var \Drupal\field\FieldConfigInterface[] $field_configs */
      $field_configs = $this->fieldConfigStorage->loadByProperties([
        'entity_type' => $configured_field->getTargetEntityTypeId(),
        'bundle' => $configured_field->getTargetBundle(),
        'field_type' => $source_field_type,
      ]);

      foreach ($field_configs as $field_config) {
        if ($this->isFieldConfigApplicable($configured_field, $field_config)) {
          $options[$field_config->getName()] = $field_config->label();
        }
      }
    }

    $base_field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($configured_field->getTargetEntityTypeId());
    foreach ($base_field_definitions as $base_field_definition) {
      if (in_array($base_field_definition->getType(), $source_field_types, TRUE) && $this->isFieldConfigApplicable($configured_field, $base_field_definition)) {
        $options[$base_field_definition->getName()] = $base_field_definition->getLabel();
      }
    }

    natcasesort($options);

    return $options;
  }

  /**
   * Helper method that builds an option array for the fallback field.
   *
   * @param \Drupal\field\FieldConfigInterface $target_field
   *   The target field.
   * @param string $source_field_name
   *   The name of the source field on which the value will be based.
   *
   * @return array
   *   An option array containing field configs.
   */
  protected function buildConverterOptions(FieldConfigInterface $target_field, string $source_field_name): array {
    $source_field = $this->fieldConfigStorage->load($target_field->getTargetEntityTypeId() . '.' . $target_field->getTargetBundle() . '.' . $source_field_name);

    if (!$source_field instanceof FieldConfigInterface) {
      $base_fields = $this->entityFieldManager->getBaseFieldDefinitions($target_field->getTargetEntityTypeId());
      $source_field = $base_fields[$source_field_name] ?? NULL;
    }

    if ($source_field === NULL) {
      return [];
    }

    $converter_definitions = $this->fieldFallbackConverterManager->getDefinitionsBySourceAndTarget(
        $source_field->getType(),
        $target_field->getType()
      );

    $options = [];
    foreach ($converter_definitions as $converter_definition) {
      $options[$converter_definition['id']] = $converter_definition['label'];
    }

    return $options;
  }

  /**
   * Checks if a field can be configured as a fallback field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $configured_field
   *   The currently configured field.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fallback_field
   *   The fallback field.
   *
   * @return bool
   *   True, when the field can be configured as a fallback field, else FALSE.
   */
  protected function isFieldConfigApplicable(FieldDefinitionInterface $configured_field, FieldDefinitionInterface $fallback_field): bool {
    // Chaining multiple fields is not supported right now.
    if ($fallback_field instanceof ThirdPartySettingsInterface && $fallback_field->getThirdPartySetting('field_fallback', 'field') !== NULL) {
      return FALSE;
    }

    // You can't use the same field as a fallback field.
    if ($configured_field->getName() === $fallback_field->getName()) {
      return FALSE;
    }

    // When a field has the current field configured as a fallback, you can't
    // use that field as a fallback field, since that would result in an
    // infinite loop.
    if ($fallback_field instanceof ThirdPartySettingsInterface && (string) $fallback_field->getThirdPartySetting('field_fallback', 'field') === $configured_field->getName()) {
      return FALSE;
    }

    // When the field is an entity reference or an entity reference revisions
    // field, check if the target types match, so it's not possible to add a
    // fallback for entity reference fields that are not referencing the same
    // entities.
    $configured_field_storage = $configured_field->getFieldStorageDefinition();
    $fallback_field_storage = $fallback_field->getFieldStorageDefinition();
    if (in_array($configured_field_storage->getType(), [
      'entity_reference',
      'entity_reference_revisions',
    ]) && in_array($fallback_field->getType(), [
      'entity_reference',
      'entity_reference_revisions',
    ]) && $configured_field_storage->getSetting('target_type') !== $fallback_field_storage->getSetting('target_type')) {
      return FALSE;
    }

    // When both fields are of the same type, converting the values will always
    // work.
    if ($configured_field_storage->getType() === $fallback_field_storage->getType()) {
      return TRUE;
    }

    // When the values are not of the same type, let's check if there is a
    // converter available that is not the default one.
    $converter_definitions = $this->fieldFallbackConverterManager->getDefinitionsBySourceAndTarget(
      $fallback_field->getType(),
      $configured_field->getType()
    );
    $available_converter_definitions = FALSE;
    foreach ($converter_definitions as $converter_definition) {
      /** @var \Drupal\field_fallback\Plugin\FieldFallbackConverterInterface $plugin_instance */
      $plugin_instance = $this->fieldFallbackConverterManager->createInstance($converter_definition['id']);
      if ($plugin_instance->getPluginId() !== 'default' && $plugin_instance->isApplicable($configured_field, $fallback_field)) {
        $available_converter_definitions = TRUE;
      }
    }

    if ($available_converter_definitions === FALSE) {
      return FALSE;
    }

    return TRUE;
  }

}
