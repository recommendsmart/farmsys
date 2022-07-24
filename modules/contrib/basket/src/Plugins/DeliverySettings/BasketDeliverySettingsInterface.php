<?php

namespace Drupal\basket\Plugins\DeliverySettings;

/**
 * Provides an interface for all Basket DeliverySettings plugins.
 */
interface BasketDeliverySettingsInterface {

  /**
   * Settings form alter.
   */
  public function settingsFormAlter(&$form, $form_state);

  /**
   * Get settings info list.
   */
  public function getSettingsInfoList($tid);

}
