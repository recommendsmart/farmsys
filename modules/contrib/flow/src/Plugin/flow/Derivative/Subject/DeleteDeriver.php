<?php

namespace Drupal\flow\Plugin\flow\Derivative\Subject;

use Drupal\flow\Plugin\flow\Derivative\ContentDeriverBase;

/**
 * Subject plugin deriver for content that is being deleted.
 *
 * @see \Drupal\flow\Plugin\flow\Subject\Delete
 */
class DeleteDeriver extends ContentDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $derivatives = parent::getDerivativeDefinitions($base_plugin_definition);
    foreach ($derivatives as &$derivative) {
      $derivative['label'] = $this->t('@content being deleted', ['@content' => $derivative['label']]);
      $derivative['targets'] = [$derivative['entity_type'] => [$derivative['bundle']]];
    }
    return $derivatives;
  }

}
