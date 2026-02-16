<?php

namespace Drupal\entity_logger\Plugin\views\field;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\entity_logger\Entity\EntityLogEntryInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Views field plugin to render human-readable severity level.
 *
 * @ViewsField("entity_log_entry_severity_label")
 */
class SeverityLabel extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $this->getEntity($values);
    if (!$entity instanceof EntityLogEntryInterface) {
      return NULL;
    }

    $severity_levels = RfcLogLevel::getLevels();
    return $severity_levels[$entity->getSeverity()];
  }

}
