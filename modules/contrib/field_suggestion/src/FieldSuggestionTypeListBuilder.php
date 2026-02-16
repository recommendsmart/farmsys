<?php

namespace Drupal\field_suggestion;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of field suggestion type entities.
 *
 * @see \Drupal\field_suggestion\Entity\FieldSuggestionType
 */
class FieldSuggestionTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);

    $instance->fieldTypePluginManager = $container->get('plugin.manager.field.field_type');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'id' => $this->t('Machine name'),
      'label' => $this->t('Title'),
      'description' => $this->t('Description'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $definition = $this->fieldTypePluginManager->getDefinition($entity->id());

    return [
      'id' => $entity->id(),
      'label' => $entity->label(),
      'description' => $definition['description'],
    ] + parent::buildRow($entity);
  }

}
