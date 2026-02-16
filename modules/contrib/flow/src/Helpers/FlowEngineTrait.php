<?php

namespace Drupal\flow\Helpers;

use Drupal\flow\Flow;

/**
 * Trait for components that make use of the Flow engine.
 */
trait FlowEngineTrait {

  /**
   * The service name of the flow engine.
   *
   * @var string
   */
  protected static $flowEngineServiceName = 'flow';

  /**
   * The Flow engine.
   *
   * @var \Drupal\flow\Flow
   */
  protected Flow $flowEngine;

  /**
   * Set the Flow engine.
   *
   * @param \Drupal\flow\Flow $engine
   *   The Flow engine.
   */
  public function setFlowEngine(Flow $engine): void {
    $this->flowEngine = $engine;
  }

  /**
   * Get the Flow engine.
   *
   * @return \Drupal\flow\Flow
   *   The Flow engine.
   */
  public function getFlowEngine(): Flow {
    if (!isset($this->flowEngine)) {
      $this->flowEngine = \Drupal::service(self::$flowEngineServiceName);
    }
    return $this->flowEngine;
  }

}
