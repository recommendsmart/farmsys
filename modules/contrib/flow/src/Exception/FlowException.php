<?php

namespace Drupal\flow\Exception;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Class for exceptions related to Flow.
 *
 * Any instance of this type of exception is not meant for an errorneous
 * situation, but instead for handling actions that are to be done outside
 * the scope of an imminent task that is to be processed. For example cases,
 * have a look at the exception classes that extend from this class, e.g.
 * \Drupal\flow\Exception\FlowEnqueueException.
 */
class FlowException extends \RuntimeException {

  /**
   * The plugin instance that created this exception.
   *
   * @var \Drupal\Component\Plugin\PluginInspectionInterface
   */
  protected $pluginInstance;

  /**
   * Constructs a new FlowException.
   *
   * @param \Drupal\Component\Plugin\PluginInspectionInterface $plugin
   *   The plugin instance that created this exception.
   * @param string $message
   *   (optional) A message that describes this exception.
   */
  public function __construct(PluginInspectionInterface $plugin, string $message = '') {
    parent::__construct($message);
    $this->pluginInstance = $plugin;
  }

  /**
   * Returns the plugin instance that created this exception.
   *
   * @return \Drupal\Component\Plugin\PluginInspectionInterface
   *   The plugin instance.
   */
  public function getPlugin(): PluginInspectionInterface {
    return $this->pluginInstance;
  }

}
