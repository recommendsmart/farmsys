<?php

namespace Drupal\basket;

/**
 * {@inheritdoc}
 */
class BasketUninstall {

  /**
   * {@inheritdoc}
   */
  public function run() {
    // Delete "entity_view_mode".
    $viewMode = \Drupal::configFactory()->getEditable('core.entity_view_mode.node.basket');
    if (!empty($viewMode)) {
      $viewMode->delete();
    }
    // Delete NODE_TYPE.
    $nodeType = \Drupal::service('entity_type.manager')->getStorage('node_type')->load('basket_order');
    if (!empty($nodeType)) {
      $nodeType->delete();
    }
    // Delete all configuration.
    $listAll = \Drupal::configFactory()->listAll('basket');
    if (!empty($listAll)) {
      foreach ($listAll as $configName) {
        \Drupal::configFactory()->getEditable($configName)->delete();
      }
    }
  }

}
