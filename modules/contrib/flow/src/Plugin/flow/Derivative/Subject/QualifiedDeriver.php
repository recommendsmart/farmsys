<?php

namespace Drupal\flow\Plugin\flow\Derivative\Subject;

use Drupal\flow\Plugin\flow\Derivative\ContentDeriverBase;

/**
 * Subject plugin deriver for qualified content.
 *
 * @see \Drupal\flow\Plugin\flow\Subject\Qualified
 */
class QualifiedDeriver extends ContentDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $derivatives = parent::getDerivativeDefinitions($base_plugin_definition);
    foreach ($derivatives as &$derivative) {
      $derivative['label'] = $this->t('Qualified @content', ['@content' => $derivative['label']]);
    }
    return $derivatives;
  }

}
