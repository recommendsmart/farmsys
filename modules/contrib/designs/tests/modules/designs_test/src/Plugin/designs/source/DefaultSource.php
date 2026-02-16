<?php

namespace Drupal\designs_test\Plugin\designs\source;

/**
 * The design source for testing region start with default source.
 *
 * @DesignSource(
 *   id = "designs_test_default",
 *   label = @Translation("Default")
 * )
 */
class DefaultSource extends BaseSource {

  /**
   * {@inheritdoc}
   */
  public function getDefaultSources() {
    return ['text'];
  }

}
