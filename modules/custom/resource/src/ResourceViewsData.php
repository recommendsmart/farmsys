<?php

/**
 * @file
 * Contains \Drupal\resource\Entity\Resource.
 */

namespace Drupal\resource;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Resource entities.
 */
class ResourceViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['resource']['resource_bulk_form'] = array(
      'title' => t('Resource operations bulk form'),
      'help' => t('Add a form element that lets you run operations on multiple resource entities.'),
      'field' => array(
        'id' => 'resource_bulk_form',
      ),
    );

    return $data;
  }

}
