<?php

namespace Drupal\flow\Helpers;

/**
 * Trait for Flow-related plugins that are configurable.
 */
trait ConfigurablePluginTrait {

  /**
   * Configuration information passed into the plugin.
   *
   * @var array
   */
  protected $configuration;

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * Get the configuration array as reference.
   *
   * This public method exists to save some overhead on runtime when
   * accessing and changing configuration values.
   *
   * @return array
   *   The configuration as reference.
   */
  public function &configuration(): array {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

}
