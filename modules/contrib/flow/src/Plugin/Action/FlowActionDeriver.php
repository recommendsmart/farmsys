<?php

namespace Drupal\flow\Plugin\Action;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\flow\Plugin\flow\Derivative\ContentDeriverBase;

/**
 * Deriver for Flow actions.
 *
 * @see \Drupal\flow\Plugin\Action\FlowAction
 */
class FlowActionDeriver extends ContentDeriverBase {

  /**
   * A statically cached list of derivative definitions.
   *
   * @var array
   */
  protected static ?array $derivatives;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (!isset(self::$derivatives)) {
      self::$derivatives = [];
      $flow_configs = $this->entityTypeManager->getStorage('flow')->loadMultiple();
      foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
        if (!($entity_type->entityClassImplements(ContentEntityInterface::class)) || !$entity_type->hasKey('uuid')) {
          continue;
        }
        $accordings = [];
        /** @var \Drupal\flow\Entity\Flow $flow */
        foreach ($flow_configs as $flow) {
          if (!$flow->status()) {
            continue;
          }
          if ($entity_type_id === $flow->getTargetEntityTypeId()) {
            $accordings[$entity_type_id . '.' . $flow->getTaskMode()] = $flow->label();
          }
        }

        foreach ($accordings as $id => $label) {
          self::$derivatives[$id] = [
            'label' => $this->t('Apply @label flow', ['@label' => $label]),
            'type' => $entity_type_id,
          ];
        }
      }
    }

    $derivatives = self::$derivatives;
    foreach ($derivatives as &$item) {
      $item += $base_plugin_definition;
    }
    unset($item);

    return $derivatives;
  }

}
