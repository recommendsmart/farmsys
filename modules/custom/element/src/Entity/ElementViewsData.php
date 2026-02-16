<?php

namespace Drupal\element\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for element entities.
 */
class ElementViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['element_field_data']['type']['argument']['id'] = 'element_type';

    $data['element_field_data']['user_id']['help'] = $this->t('The user authoring the element. If you need more fields than the uid add the element author relationship');
    $data['element_field_data']['user_id']['filter']['id'] = 'user_name';
    $data['element_field_data']['user_id']['relationship']['title'] = $this->t('Element author');
    $data['element_field_data']['user_id']['relationship']['help'] = $this->t('Relate Element to the user who created them.');
    $data['element_field_data']['user_id']['relationship']['label'] = $this->t('author');

    return $data;
  }

}
