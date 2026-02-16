<?php

/**
 * @file
 * Contains \Drupal\resource\ResourceListBuilder.
 */

namespace Drupal\resource;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Resource entities.
 *
 * @ingroup resource
 */
class ResourceListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Resource ID');
    $header['name'] = $this->t('Name');
    $header['type'] = $this->t('Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\resource\Entity\Resource */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      $entity->toUrl('canonical')
    );

    // @todo Show type name.
    $row['type'] = $entity->bundle();
    return $row + parent::buildRow($entity);
  }

}
