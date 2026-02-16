<?php

namespace Drupal\storage\Plugin\views\field;

use Drupal\views\Plugin\views\field\BulkForm;

/**
 * Defines a Storage operations bulk form element.
 *
 * @ViewsField("storage_bulk_form")
 */
class StorageBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No data selected.');
  }

}
