<?php

namespace Drupal\group_field_bundle\Plugin\GroupContentEnabler;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\field_bundle\Entity\FieldBundleConfig;

class GroupFieldBundleDeriver extends DeriverBase {

  /**
   * {@inheritdoc}.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    foreach (FieldBundleConfig::loadMultiple() as $name => $node_type) {
      $label = $node_type->label();

      $this->derivatives[$name] = [
        'entity_bundle' => $name,
        'label' => t('Group field bundle (@type)', ['@type' => $label]),
        'description' => t('Adds %type field bundles to groups both publicly and privately.', ['%type' => $label]),
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
