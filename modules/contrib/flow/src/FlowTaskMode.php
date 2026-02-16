<?php

namespace Drupal\flow;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Holds information about known Flow task modes.
 */
class FlowTaskMode {

  /**
   * The list of available task modes.
   *
   * @var array
   */
  protected array $taskModes;

  /**
   * The FlowTaskMode constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, TranslationInterface $string_translation) {
    $this->taskModes = [
      'create' => $string_translation->translate('Create'),
      'save' => $string_translation->translate('Save'),
      'delete' => $string_translation->translate('Delete'),
    ];
    $module_handler->alter('flow_task_modes', $this->taskModes);
  }

  /**
   * Get the Flow task mode service.
   *
   * @return \Drupal\flow\FlowTaskMode
   *   The Flow task mode service.
   */
  public static function service(): FlowTaskMode {
    return \Drupal::service('flow.task.mode');
  }

  /**
   * Get available task modes.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   The task modes, keyed by machine name, values are translatable labels.
   */
  public function getAvailableTaskModes(): array {
    return $this->taskModes;
  }

  /**
   * Get the default task mode.
   *
   * @return string
   *   The machine name of the default task mode.
   */
  public function getDefaultTaskMode(): string {
    $task_modes = $this->getAvailableTaskModes();
    return isset($task_modes['save']) ? 'save' : key($task_modes);
  }

}
