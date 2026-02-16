<?php

namespace Drupal\flow\Plugin\flow\Derivative\Task;

use Drupal\flow\Plugin\flow\Derivative\ContentDeriverBase;

/**
 * Task plugin deriver for merging content values.
 *
 * @see \Drupal\flow\Plugin\flow\Task\Merge
 */
class MergeDeriver extends ContentDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $derivatives = parent::getDerivativeDefinitions($base_plugin_definition);
    foreach ($derivatives as &$derivative) {
      $derivative['label'] = $this->t('Merge values from @content', ['@content' => $derivative['label']]);
    }
    return $derivatives;
  }

}
