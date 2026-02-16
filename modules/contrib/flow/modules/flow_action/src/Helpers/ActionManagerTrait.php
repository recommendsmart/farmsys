<?php

namespace Drupal\flow_action\Helpers;

use Drupal\Core\Action\ActionManager;

/**
 * Trait for components making use of Core's action manager.
 */
trait ActionManagerTrait {

  /**
   * The service name of the action manager.
   *
   * @var string
   */
  protected static $actionManagerServiceName = 'plugin.manager.action';

  /**
   * The action manager.
   *
   * @var \Drupal\Core\Action\ActionManager
   */
  protected ActionManager $actionManager;

  /**
   * Set the action manager.
   *
   * @param \Drupal\Core\Action\ActionManager $action_manager
   *   The action manager.
   */
  public function setActionManager(ActionManager $action_manager): void {
    $this->actionManager = $action_manager;
  }

  /**
   * Get the action manager.
   *
   * @return \Drupal\Core\Action\ActionManager
   *   The action manager.
   */
  public function getActionManager(): ActionManager {
    if (!isset($this->actionManager)) {
      $this->actionManager = \Drupal::service(self::$actionManagerServiceName);
    }
    return $this->actionManager;
  }

}
