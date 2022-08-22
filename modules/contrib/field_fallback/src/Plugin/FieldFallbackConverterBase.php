<?php

namespace Drupal\field_fallback\Plugin;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for field fallback converters.
 */
abstract class FieldFallbackConverterBase extends PluginBase implements FieldFallbackConverterInterface {

  /**
   * The field's parent entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The target field. The field used to save the fallback value.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $targetField;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): void {
    $this->configuration = NestedArray::mergeDeep(
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {

  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(FieldDefinitionInterface $target_field, FieldDefinitionInterface $source_field): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSource(): array {
    return $this->pluginDefinition['source'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTarget(): array {
    return $this->pluginDefinition['target'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return $this->pluginDefinition['weight'] ?? 0;
  }

  /**
   * Get the entity for which the value is converted.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity when found, else NULL.
   */
  protected function getEntity(): ?EntityInterface {
    return $this->entity;
  }

  /**
   * Get the target entity in which the value is saved.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface|null
   *   The field config when found, else NULL.
   */
  protected function getTargetField(): ?FieldDefinitionInterface {
    return $this->targetField;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(FieldableEntityInterface $entity): FieldFallbackConverterInterface {
    $this->entity = $entity;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetField(FieldDefinitionInterface $field_config): FieldFallbackConverterInterface {
    $this->targetField = $field_config;
    return $this;
  }

}
