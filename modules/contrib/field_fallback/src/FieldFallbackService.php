<?php

namespace Drupal\field_fallback;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\field_fallback\Plugin\FieldFallbackConverterManagerInterface;

/**
 * The field fallback service.
 */
class FieldFallbackService {

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
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Static caching of the fallback fields.
   *
   * @var array
   */
  protected $fallbackFields = [];

  /**
   * FieldFallbackService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\field_fallback\Plugin\FieldFallbackConverterManagerInterface $field_fallback_converter_manager
   *   The field fallback_converter_manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FieldFallbackConverterManagerInterface $field_fallback_converter_manager, LanguageManagerInterface $language_manager) {
    $this->fieldConfigStorage = $entity_type_manager->getStorage('field_config');
    $this->fieldFallbackConverterManager = $field_fallback_converter_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * Assigns the values of the fallback fields to multiple entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The entities.
   */
  public function assignFallbackFieldValuesToEntities(array $entities): void {
    foreach ($entities as $entity) {
      $this->assignFallbackFieldValuesToEntity($entity);
    }
  }

  /**
   * Assigns the values of the fallback fields to the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  public function assignFallbackFieldValuesToEntity(EntityInterface $entity): void {
    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }

    $fallback_fields = $this->getFieldsWithFallbackConfigured($entity->getEntityTypeId(), $entity->bundle());
    if (empty($fallback_fields)) {
      return;
    }

    // Get the translated entity, when available.
    $language_id = $this->languageManager->getCurrentLanguage()->getId();
    if ($entity instanceof TranslatableInterface && $entity->hasTranslation($language_id)) {
      $entity = $entity->getTranslation($language_id);
    }

    foreach ($fallback_fields as $field => $fallback_field) {
      if (!$entity->hasField($field) || !$entity->hasField($fallback_field['field']) || !$entity->get($field)->isEmpty()) {
        continue;
      }

      if ($this->fieldFallbackConverterManager->hasDefinition($fallback_field['converter'])) {
        /** @var \Drupal\field_fallback\Plugin\FieldFallbackConverterInterface $converter */
        $converter = $this->fieldFallbackConverterManager->createInstance($fallback_field['converter'], $fallback_field['configuration'] ?? []);
        $converter->setEntity($entity);
        $converter->setTargetField($entity->get($field)->getFieldDefinition());

        $value = $converter->convert($entity->get($fallback_field['field']));
        $cardinality = $entity->get($field)->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();

        if (!is_array($value) || !isset($value[0])) {
          $value = [$value];
        }

        // Limit the values to the configured cardinality of the field.
        if ($cardinality !== -1) {
          $value = array_slice($value, 0, $cardinality);
        }

        $entity->get($field)->setValue($value, FALSE);
        $entity->fallbackFields[] = $field;
      }
    }
  }

  /**
   * Remove the fallback values from the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  public function removeFallbackFieldValuesFromEntity(EntityInterface $entity): void {
    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }

    $fallback_fields = $this->getFieldsWithFallbackConfigured($entity->getEntityTypeId(), $entity->bundle());
    if (empty($fallback_fields)) {
      return;
    }

    foreach ($fallback_fields as $field => $fallback_field) {
      if (!isset($entity->fallbackFields) || !in_array($field, $entity->fallbackFields, TRUE) || !$entity->hasField($field) || !$entity->hasField($fallback_field['field'])) {
        continue;
      }

      $entity->set($field, NULL);
    }
  }

  /**
   * Get a list of fallback fields.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   *
   * @return array
   *   An array of fallback fields keyed by the field, value .
   */
  protected function getFieldsWithFallbackConfigured(string $entity_type_id, string $bundle): array {
    if (!isset($this->fallbackFields[$entity_type_id][$bundle])) {
      $fallback_fields = [];
      $field_config_ids = $this->getFieldConfigIdsWithFallback($entity_type_id, $bundle);

      if (!empty($field_config_ids)) {
        /** @var \Drupal\field\FieldConfigInterface[] $field_configs */
        $field_configs = $this->fieldConfigStorage->loadMultiple($field_config_ids);

        foreach ($field_configs as $field_config) {
          $fallback_fields[$field_config->getName()] = $field_config->getThirdPartySettings('field_fallback');
        }
      }

      $this->fallbackFields[$entity_type_id][$bundle] = $fallback_fields;
    }

    return $this->fallbackFields[$entity_type_id][$bundle];
  }

  /**
   * Cleanup the config when a field is deleted.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $deleted_field
   *   The field that's being deleted.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function cleanupConfigFields(FieldDefinitionInterface $deleted_field): void {
    $this->doCleanupConfigOnDeletion(
      $deleted_field->getTargetEntityTypeId(),
      $deleted_field->getTargetBundle() ?? $deleted_field->getTargetEntityTypeId(),
      $deleted_field->getName()
    );
  }

  /**
   * Cleanup the config when a base field is deleted.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $deleted_base_field
   *   The base field that's being deleted.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function cleanupConfigBaseFields(FieldStorageDefinitionInterface $deleted_base_field): void {
    $this->doCleanupConfigOnDeletion(
      $deleted_base_field->getTargetEntityTypeId(),
      NULL,
      $deleted_base_field->getName()
    );
  }

  /**
   * Method that does the actual cleanup of the field configs.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|null $bundle_id
   *   The bundle ID, when available.
   * @param string $deleted_field_name
   *   The name of the field that's being deleted.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function doCleanupConfigOnDeletion(string $entity_type_id, ?string $bundle_id, string $deleted_field_name): void {
    $field_config_ids = $this->getFieldConfigIdsWithFallback($entity_type_id, $bundle_id);
    if (empty($field_config_ids)) {
      return;
    }

    /** @var \Drupal\field\FieldConfigInterface[] $field_configs */
    $field_configs = $this->fieldConfigStorage->loadMultiple($field_config_ids);

    foreach ($field_configs as $field_config) {
      if ($field_config->getThirdPartySetting('field_fallback', 'field') === $deleted_field_name) {
        $field_config->unsetThirdPartySetting('field_fallback', 'field');
        $field_config->unsetThirdPartySetting('field_fallback', 'converter');
        $field_config->save();
      }
    }
  }

  /**
   * Get field for the given entity and bundle with a fallback field configured.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|null $bundle
   *   The bundle.
   *
   * @return array
   *   An array of field config IDs.
   */
  protected function getFieldConfigIdsWithFallback(string $entity_type_id, ?string $bundle): array {
    $query = $this->fieldConfigStorage->getQuery();
    $query->condition('entity_type', $entity_type_id);

    if ($bundle !== NULL) {
      $query->condition('bundle', $bundle);
    }

    $query->condition('third_party_settings.field_fallback.field', NULL, 'IS NOT NULL');
    $result = $query->execute();
    return is_array($result) ? $result : [];
  }

}
