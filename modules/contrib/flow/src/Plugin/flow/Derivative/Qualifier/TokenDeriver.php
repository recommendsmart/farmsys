<?php

namespace Drupal\flow\Plugin\flow\Derivative\Qualifier;

use Drupal\flow\Plugin\flow\Derivative\ContentDeriverBase;

/**
 * Qualifier plugin deriver for token content.
 *
 * @see \Drupal\flow\Plugin\flow\Qualifier\Token
 */
class TokenDeriver extends ContentDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $derivatives = parent::getDerivativeDefinitions($base_plugin_definition);
    foreach ($derivatives as &$derivative) {
      $derivative['label'] = $this->t('Token matching @content', ['@content' => $derivative['label']]);
    }
    return $derivatives;
  }

}
