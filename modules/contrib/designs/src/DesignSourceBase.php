<?php

namespace Drupal\designs;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Render\Element;

/**
 * Provides base plugin for design sources.
 */
abstract class DesignSourceBase extends PluginBase implements DesignSourceInterface {

  use DependencySerializationTrait;

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getContexts(array &$element) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSources(array $sources, array $element) {
    $sources = [];
    foreach (Element::children($element) as $child) {
      $sources[$child] = $element[$child];
    }
    return $sources;
  }

  /**
   * {@inheritdoc}
   */
  public function usesCustomContent() {
    return $this->pluginDefinition['usesCustomContent'];
  }

  /**
   * {@inheritdoc}
   */
  public function usesRegionsForm() {
    return $this->pluginDefinition['usesRegionsForm'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSources() {
    return $this->pluginDefinition['defaultSources'] ?? [];
  }

}
