<?php

namespace Drupal\flow\Helpers;

/**
 * Trait for plugins that have settings.
 */
trait PluginWithSettingsTrait {

  /**
   * The plugin settings.
   *
   * @var array
   */
  protected array $settings = [];

  /**
   * Constructs a new plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->configuration = $configuration;
    if (!isset($this->configuration['settings'])) {
      $this->configuration['settings'] = [];
    }
    if (!isset($this->configuration['third_party_settings'])) {
      $this->configuration['third_party_settings'] = [];
    }
    $this->settings = &$this->configuration['settings'];
    $this->thirdPartySettings = &$this->configuration['third_party_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings(): array {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings): void {
    $this->settings = $settings;
  }

  /**
   * Get the settings array as reference.
   *
   * This public method exists to save some overhead on runtime when
   * accessing and changing settings values.
   *
   * @return array
   *   The settings as reference.
   */
  public function &settings(): array {
    return $this->settings;
  }

}
