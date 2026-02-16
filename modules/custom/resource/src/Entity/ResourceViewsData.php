<?php

namespace Drupal\resource\Entity;

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

    $data['resource']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Resource'),
      'help' => $this->t('The Resource ID.'),
    );

    return $data;
  }

}
