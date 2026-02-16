<?php

namespace Drupal\designs_field\Plugin\designs\source;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\designs\DesignPropertiesInterface;
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
   * The design properties.
   *
   * @var \Drupal\designs\DesignPropertiesInterface
   */
  protected $designProperties;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManagerInterface $entityFieldManager, FieldTypePluginManagerInterface $fieldTypeManager, DesignPropertiesInterface $designProperties) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFieldManager = $entityFieldManager;
    $this->fieldTypeManager = $fieldTypeManager;
    $this->designProperties = $designProperties;
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
      $container->get('plugin.manager.field.field_type'),
      $container->get('design.properties')
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
      $output[$prop_id] = $this->designProperties->getMarkup($item->get($prop_id)
        ->getValue());
    }

    return $output;
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
