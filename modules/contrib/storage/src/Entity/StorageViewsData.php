<?php

namespace Drupal\storage\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Storage entities.
 */
class StorageViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['storage_field_data']['table']['wizard_id'] = 'storage';
    $data['storage_field_revision']['table']['wizard_id'] = 'storage_revision';

    $data['storage_revision']['revision_user']['help'] = $this->t('The user who created the revision.');
    $data['storage_revision']['revision_user']['relationship']['label'] = $this->t('revision user');
    $data['storage_revision']['revision_user']['filter']['id'] = 'user_name';

    $data['storage_revision']['table']['join']['storage_field_data']['left_field'] = 'vid';
    $data['storage_revision']['table']['join']['storage_field_data']['field'] = 'vid';

    $data['storage_field_data']['status_extra'] = [
      'title' => $this->t('Published status or admin user'),
      'help' => $this->t('Filters out unpublished storage if the current user cannot view it.'),
      'filter' => [
        'field' => 'status',
        'id' => 'storage_status',
        'label' => $this->t('Published status or admin user'),
      ],
    ];

    return $data;
  }

}
