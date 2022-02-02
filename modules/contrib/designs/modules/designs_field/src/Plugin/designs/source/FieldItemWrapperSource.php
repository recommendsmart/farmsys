<?php

namespace Drupal\designs_field\Plugin\designs\source;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\designs\DesignSourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The source using field item wrapper.
 *
 * @DesignSource(
 *   id = "field_item_wrapper",
 *   label = @Translation("Field item wrapper"),
 *   defaultSources = {
 *     "content"
 *   }
 * )
 */
class FieldItemWrapperSource extends DesignSourceBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The field type manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManagerInterface $entityFieldManager, FieldTypePluginManagerInterface $fieldTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFieldManager = $entityFieldManager;
    $this->fieldTypeManager = $fieldTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type')
    );
  }

  /**
   * Get the field type.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The typed data definition.
   */
  protected function getType() {
    $definitions = $this->entityFieldManager->getFieldDefinitions(
      $this->configuration['type'],
      $this->configuration['bundle'],
    );
    $definition = $definitions[$this->configuration['field']];

    // Get the type based on the field definition.
    return $this->fieldTypeManager->createInstance(
      $definition->getType(),
      [
        'name' => NULL,
        'parent' => NULL,
        'field_definition' => $definition,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSources() {
    $sources = [
      'delta' => $this->t('Delta'),
      'content' => $this->t('Content'),
    ];

    // Process the data definition for a field.
    $definition = $this->getType()->getDataDefinition();
    foreach ($definition->getPropertyDefinitions() as $prop_id => $property) {
      $sources["prop_{$prop_id}"] = $property->getLabel();
    }

    return $sources;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSources(array $sources, array $element) {
    $output = [
      'delta' => $this->configuration['delta'],
      'content' => $element,
    ];
    $item = $element['#item'];

    // Cycle through all the properties for the field type.
    $definition = $this->getType()->getDataDefinition();
    foreach ($definition->getPropertyDefinitions() as $prop_id => $property) {
      $output[$prop_id] = $this->getMarkup($item->get($prop_id)->getValue());
    }

    return $output;
  }

  /**
   * Get the markup from the value.
   *
   * @param mixed $value
   *   A property value.
   *
   * @return array
   *   The render array.
   */
  protected function getMarkup($value) {
    if (is_scalar($value)) {
      return [
        '#markup' => $value,
      ];
    }
    elseif (method_exists($value, 'toString')) {
      return [
        '#markup' => $value->toString(),
      ];
    }
    elseif (method_exists($value, '__toString')) {
      return [
        '#markup' => (string) $value,
      ];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getContexts(array &$element) {
    return [
      $element['#entity_type'] => $element['#object'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormContexts() {
    $entity_type = $this->configuration['type'];
    return parent::getFormContexts() + [
      $entity_type => EntityContextDefinition::fromEntityTypeId($entity_type),
    ];
  }

}
