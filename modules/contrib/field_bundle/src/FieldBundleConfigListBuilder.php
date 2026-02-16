<?php

namespace Drupal\field_bundle;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of field bundle config entities.
 *
 * @see \Drupal\field_bundle\Entity\FieldBundleConfig
 */
class FieldBundleConfigListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = $this->t('Label');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = [
      'data' => $entity->label(),
      'class' => ['menu-label'],
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No field bundle configurations available. <a href=":link">Add field bundle config</a>.',
      [':link' => Url::fromRoute('entity.field_bundle_config.add_form')->toString()]
    );

    return $build;
  }

}
