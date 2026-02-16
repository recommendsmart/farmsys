<?php

namespace Drupal\flow\Plugin\flow\Derivative\Qualifier;

use Drupal\flow\Plugin\flow\Derivative\ContentDeriverBase;

/**
 * Qualifier plugin deriver for congruent content.
 *
 * @see \Drupal\flow\Plugin\flow\Qualifier\Congruency
 */
class CongruentDeriver extends ContentDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $derivatives = parent::getDerivativeDefinitions($base_plugin_definition);
    foreach ($derivatives as &$derivative) {
      $derivative['label'] = $this->t('Congruent @content', ['@content' => $derivative['label']]);
    }
    return $derivatives;
  }

}
