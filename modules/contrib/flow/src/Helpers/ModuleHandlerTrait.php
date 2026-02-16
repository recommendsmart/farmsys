<?php

namespace Drupal\flow\Helpers;

use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Trait for components that make use of the module handler.
 */
trait ModuleHandlerTrait {

  /**
   * The service name of the form builder.
   *
   * @var string
   */
  protected static $moduleHandlerServiceName = 'module_handler';

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Get the module handler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  public function getModuleHandler(): ModuleHandlerInterface {
    if (!isset($this->moduleHandler)) {
      $this->moduleHandler = \Drupal::service(self::$moduleHandlerServiceName);
    }
    return $this->moduleHandler;
  }

  /**
   * Set the module handler.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function setModuleHandler(ModuleHandlerInterface $module_handler): void {
    $this->moduleHandler = $module_handler;
  }

}
