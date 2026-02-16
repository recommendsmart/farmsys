<?php

namespace Drupal\designs_field\Plugin\designs\source;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\designs\DesignSourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The source using field wrapper.
 *
 * @DesignSource(
 *   id = "field_wrapper",
 *   label = @Translation("Field wrapper"),
 *   defaultSources = {
 *     "content"
 *   }
 * )
 */
class FieldWrapperSource extends DesignSourceBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManagerInterface $entityFieldManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSources() {
    // Field wrapper doesn't care, except for the number of elements.
    $field_definitions = $this->entityFieldManager->getFieldDefinitions(
      $this->configuration['type'],
      $this->configuration['bundle'],
    );
    $config = $field_definitions[$this->configuration['field']];

    $max_cardinality = 5;
    $cardinality = $config->getFieldStorageDefinition()->getCardinality();
    if ($cardinality === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $cardinality = $max_cardinality;
    }

    $sources = [
      'label' => $this->t('Label'),
      'content' => $this->t('Value'),
    ];
    if ($cardinality < 2) {
      return $sources;
    }
    $sources['content'] = $this->t('All values');
    $sources['remainder'] = $this->t('Remaining values');

    // Allow a maximum of cardinality positions to be specified.
    $cardinality = min($cardinality, $max_cardinality);
    for ($i = 0; $i < $cardinality; $i++) {
      $sources[$i] = $this->t('Value from position @position', ['@position' => $i]);
    }

    return $sources;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSources(array $sources, array $element) {
    $results = [
      'content' => [],
      'remainder' => [],
    ];

    // Setup the label based on template_preprocess_form_element().
    if (!empty($element['#formatter'])) {
      $results['label'] = ['#markup' => $element['#title']];
    }
    else {
      $results['label'] = [
        '#theme' => 'form_element_label',
        '#attributes' => [],
      ];
      $results['label'] += array_intersect_key(
        $element,
        array_flip(['#id', '#required', '#title', '#title_display'])
      );
      if (!empty($element['#label_for'])) {
        $results['label']['#for'] = $element['#label_for'];
        if (!empty($element['#id'])) {
          $results['label']['#id'] = $element['#id'] . '--label';
        }
      }
    }

    // Process each of the child elements, to generate the appropriate content.
    foreach (Element::children($element) as $child) {
      $results['content'][$child] = $element[$child];
      if (in_array($child, $sources)) {
        $results[$child] = $element[$child];
      }
      else {
        $results['remainder'] = $element[$child];
      }
    }

    return $results;
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
