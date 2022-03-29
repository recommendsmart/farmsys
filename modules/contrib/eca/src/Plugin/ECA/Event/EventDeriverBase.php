<?php

namespace Drupal\eca\Plugin\ECA\Event;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 *
 */
abstract class EventDeriverBase extends DeriverBase {

  /**
   * @return array
   */
  abstract protected function actions(): array;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    $this->derivatives = [];
    foreach ($this->actions() as $action_id => $action) {
      $this->derivatives[$action_id] = [
          'event_name' => $action['event_name'],
          'event_class' => $action['event_class'],
          'action' => $action_id,
          'label' => $action['label'],
          'tags' => $action['tags'] ?? 0,
        ] + $base_plugin_definition;
    }
    return $this->derivatives;
  }

}
