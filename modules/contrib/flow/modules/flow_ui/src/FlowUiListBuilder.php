<?php

namespace Drupal\flow_ui;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a UI listing of Flow config entities.
 *
 * @see \Drupal\flow\Entity\Flow
 */
class FlowUiListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['type'] = $this->t('Type');
    $header['bundle'] = $this->t('Bundle');
    $header['mode'] = $this->t('Mode');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\flow\Entity\FlowInterface $entity */
    $row['type'] = $entity->getTargetEntityTypeId();
    $row['bundle'] = $entity->getTargetBundle();
    $row['mode'] = $entity->getTaskMode();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'Currently no Flow items are available. Have a look at the <a href=":url" target="_blank" rel="noreferrer noopener">README</a> for knowing how to create your first flow.',
      [':url' => 'https://git.drupalcode.org/project/flow/-/blob/1.0.x/README.md#4-usage']
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\flow\Entity\FlowInterface $flow */
    $flow = $entity;

    $operations = [];

    $target_type = \Drupal::entityTypeManager()->getDefinition($flow->getTargetEntityTypeId());
    $bundle_type_id = $target_type->getBundleEntityType() ?: 'bundle';

    $operations['edit'] = [
      'title' => t('Edit'),
      'weight' => 10,
      'url' => Url::fromRoute("entity.flow.{$target_type->id()}.task_mode", [
        'entity_type_id' => $target_type->id(),
        $bundle_type_id => $flow->getTargetBundle(),
        'flow_task_mode' => $flow->getTaskMode(),
      ]),
    ];

    return $operations;
  }

}
