<?php

namespace Drupal\group_storage\Plugin\GroupContentEnabler;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\storage\Entity\StorageType;

class GroupStorageDeriver extends DeriverBase {

  /**
   * {@inheritdoc}.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];
    foreach (StorageType::loadMultiple() as $name => $storage_type) {
      $label = $storage_type->label();
      $this->derivatives[$name] = [
          'entity_bundle' => $name,
          'label' => t('Group storage (@type)', ['@type' => $label]),
          'description' => t('Adds %type storage to groups both publicly and privately.', ['%type' => $label]),
        ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
