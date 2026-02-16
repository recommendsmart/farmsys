<?php

namespace Drupal\designs_view\Plugin\designs\source;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\designs\DesignSourceBase;

/**
 * The source providing views area sources.
 *
 * @DesignSource(
 *   id = "views_area",
 *   label = @Translation("Views area")
 * )
 */
class ViewsAreaSource extends DesignSourceBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getSources() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSources(array $sources, array $element) {
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getContexts(array &$element) {
    return [
      'view' => $element['#view'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSources() {
    return $this->configuration;
  }

}
