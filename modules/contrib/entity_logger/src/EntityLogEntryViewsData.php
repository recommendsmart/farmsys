<?php

namespace Drupal\entity_logger;

use Drupal\views\EntityViewsData;

/**
 * Provides extra views data for entity_log_entry entities.
 */
class EntityLogEntryViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['entity_logger']['severity_label'] = [
      'title' => $this->t('Severity (label)'),
      'help' => $this->t('Displays the severity level as human-readable label'),
      'field' => [
        'id' => 'entity_log_entry_severity_label',
      ],
    ];
    return $data;
  }

}
