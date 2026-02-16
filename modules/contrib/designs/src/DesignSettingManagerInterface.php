<?php

namespace Drupal\designs;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Interface for the design setting plugin manager.
 */
interface DesignSettingManagerInterface extends PluginManagerInterface {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\designs\DesignSettingInterface
   *   The design setting plugin.
   */
  public function createInstance($plugin_id, array $configuration = []);

}
