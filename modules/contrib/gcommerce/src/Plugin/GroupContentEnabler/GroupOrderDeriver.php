<?php

namespace Drupal\gcommerce\Plugin\GroupContentEnabler;

use Drupal\commerce_order\Entity\OrderType;
use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Provides a deriver for group_order.
 */
class GroupOrderDeriver extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach (OrderType::loadMultiple() as $name => $entity_type) {
      $label = $entity_type->label();

      $this->derivatives[$name] = [
        'entity_bundle' => $name,
        'label' => t('Group commerce order (@type)', ['@type' => $label]),
        'description' => t('Adds %type content to groups both publicly and privately.', ['%type' => $label]),
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
