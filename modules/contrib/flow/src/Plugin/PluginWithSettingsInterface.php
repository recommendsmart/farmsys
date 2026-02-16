<?php

namespace Drupal\flow\Plugin;

/**
 * Interface for plugins that have settings.
 */
interface PluginWithSettingsInterface {

  /**
   * Get the plugin settings.
   *
   * @return array
   *   The plugin settings.
   */
  public function getSettings(): array;

  /**
   * Set the plugin settings.
   *
   * @param array $settings
   *   The settings.
   */
  public function setSettings(array $settings): void;

}
