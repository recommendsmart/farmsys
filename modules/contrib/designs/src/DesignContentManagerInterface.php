<?php

namespace Drupal\designs;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Provides an interface for design content management.
 */
interface DesignContentManagerInterface extends PluginManagerInterface {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\designs\DesignContentInterface
   *   The design content plugin.
   */
  public function createInstance($plugin_id, array $configuration = []);

  /**
   * Get the source definitions.
   *
   * @param string $target
   *   One of 'setting' or 'content'.
   * @param string $source
   *   The design source plugin identifier.
   *
   * @return array[]
   *   The definitions matching source and target.
   */
  public function getSourceDefinitions($target, $source);

}
