<?php

namespace Drupal\designs;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Interface for the design source plugin manager.
 */
interface DesignSourceManagerInterface extends PluginManagerInterface {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\designs\DesignSourceInterface
   *   The design source plugin.
   */
  public function createInstance($plugin_id, array $configuration = []);

}
