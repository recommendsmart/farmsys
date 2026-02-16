<?php

namespace Drupal\designs_test\Plugin\designs\source;

use Drupal\designs\DesignSourceBase;

/**
 * Provide a common base source for the testing sources.
 */
class BaseSource extends DesignSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getSources() {
    return [
      'text' => $this->t('Text'),
      'content' => $this->t('Content'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getContexts(array &$element) {
    return [];
  }

}
