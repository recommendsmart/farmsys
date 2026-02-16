<?php

namespace Drupal\designs_view\Plugin\designs\source;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\designs\DesignSourceBase;

/**
 * The source providing views row sources.
 *
 * @DesignSource(
 *   id = "views_row",
 *   label = @Translation("Views row")
 * )
 */
class ViewsRowSource extends DesignSourceBase {

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
      'view-row' => $element,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormContexts() {
    return parent::getFormContexts() + [
      'view-row' => new ContextDefinition("map"),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSources() {
    return array_keys($this->configuration);
  }

}
