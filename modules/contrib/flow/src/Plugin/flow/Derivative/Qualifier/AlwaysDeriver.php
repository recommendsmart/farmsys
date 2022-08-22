<?php

namespace Drupal\flow\Plugin\flow\Derivative\Qualifier;

use Drupal\flow\Plugin\flow\Derivative\ContentDeriverBase;

/**
 * Qualifier plugin deriver for always qualified content.
 *
 * @see \Drupal\flow\Plugin\flow\Qualifier\Always
 */
class AlwaysDeriver extends ContentDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $derivatives = parent::getDerivativeDefinitions($base_plugin_definition);
    foreach ($derivatives as &$derivative) {
      $derivative['label'] = $this->t('Always qualified @content', ['@content' => $derivative['label']]);
    }
    return $derivatives;
  }

}
