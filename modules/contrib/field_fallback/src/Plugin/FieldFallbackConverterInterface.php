<?php

namespace Drupal\field_fallback\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Interface for field fallback converter plugins.
 */
interface FieldFallbackConverterInterface extends PluginInspectionInterface, ConfigurableInterface, PluginFormInterface {

  /**
   * Get the source fields for which the plugin calculates the fallback value.
   *
   * @return string[]
   *   An array of source fields.
   */
  public function getSource(): array;

  /**
   * Get the target fields for which the plugin calculates the fallback value.
   *
   * @return string[]
   *   An array of target fields.
   */
  public function getTarget(): array;

  /**
   * Get the weight of the plugin.
   *
   * @return int
   *   The weight of the plugin.
   */
  public function getWeight(): int;

  /**
   * Converts the value of the given field to a new value.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The source field. The field used to calculate the value.
   *
   * @return mixed
   *   The converted value.
   */
  public function convert(FieldItemListInterface $field);

  /**
   * Set the entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return $this
   *   the called converter.
   */
  public function setEntity(FieldableEntityInterface $entity): FieldFallbackConverterInterface;

  /**
   * Set the target field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_config
   *   The target field.
   *
   * @return $this
   *   the called converter.
   */
  public function setTargetField(FieldDefinitionInterface $field_config): FieldFallbackConverterInterface;

  /**
   * Method that checks if the converter is applicable for the given fields.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $target_field
   *   The target field.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $source_field
   *   The source field.
   *
   * @return bool
   *   A boolean indicating whether the converter is available.
   */
  public function isApplicable(FieldDefinitionInterface $target_field, FieldDefinitionInterface $source_field): bool;

}
