<?php

namespace Drupal\designs_entity\Plugin\designs\source;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\designs\DesignSourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The design source for entity view modes.
 *
 * @DesignSource(
 *   id = "entity",
 *   label = @Translation("Entity"),
 *   usesRegionsForm = FALSE,
 *   defaultSources = {
 *     "form:actions"
 *   }
 * )
 */
class EntitySource extends DesignSourceBase implements ContainerFactoryPluginInterface {

  /**
   * The entity display.
   *
   * @var \Drupal\Core\Entity\Display\EntityDisplayInterface
   */
  protected $owner;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManagerInterface $entityFieldManager, ModuleHandlerInterface  $moduleHandler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFieldManager = $entityFieldManager;
    $this->moduleHandler = $moduleHandler;
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
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSources() {
    $type_id = $this->configuration['type'] ?? '';
    $bundle = $this->configuration['bundle'] ?? $type_id;
    $context = $this->configuration['form'] ? 'form' : 'view';

    // Generate the fields that will be displayed for the entity field source.
    $fields = [];
    if ($this->configuration['form']) {
      $fields['form:actions'] = $this->t('Form Actions');
    }
    if ($this->moduleHandler->moduleExists('menu_ui')) {
      $fields['menu'] = $this->t('Menu');
    }
    foreach ($this->entityFieldManager->getFieldDefinitions($type_id, $bundle) as $name => $definition) {
      if ($definition->isDisplayConfigurable($context)) {
        $fields[$name] = $definition->getLabel();
      }
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSources(array $sources, array $element) {
    $sources = parent::getElementSources($sources, $element);
    if ($this->configuration['form']) {
      $sources['form:actions'] = $element['actions'];
      if (!empty($element['menu'])) {
        $sources['menu'] = $element['menu'];
      }
    }
    return $sources;
  }

  /**
   * {@inheritdoc}
   */
  public function getContexts(array &$element) {
    // Provide entity as context.
    $contexts = [];
    $entity_type = $this->configuration['type'];
    if (isset($element["#{$entity_type}"])) {
      $contexts[$entity_type] = $element["#{$entity_type}"];
    }
    return $contexts + parent::getContexts($element);
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
