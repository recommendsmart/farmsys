<?php

namespace Drupal\designs;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an interface for design content.
 */
interface DesignContentInterface extends PluginInspectionInterface, DerivativeInspectionInterface, ConfigurableInterface, DependentPluginInterface, PluginFormInterface {

  /**
   * Get the label of the setting.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The label.
   */
  public function label();

  /**
   * Create the render element for the design content.
   *
   * @param array $element
   *   The source render array.
   *
   * @return array
   *   The render array.
   */
  public function build(array &$element);

  /**
   * Get the used source keys.
   *
   * @return string[]
   *   The used sources by key.
   */
  public function getUsedSources();

}
