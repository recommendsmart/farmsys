<?php

namespace Drupal\flow\Plugin\flow\Derivative\Subject;

use Drupal\flow\Plugin\flow\Derivative\ContentDeriverBase;

/**
 * Subject plugin deriver for creating new content.
 *
 * @see \Drupal\flow\Plugin\flow\Subject\NewContent
 */
class NewContentDeriver extends ContentDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $derivatives = parent::getDerivativeDefinitions($base_plugin_definition);
    foreach ($derivatives as &$derivative) {
      $derivative['label'] = $this->t('New @content', ['@content' => $derivative['label']]);
    }
    return $derivatives;
  }

}
