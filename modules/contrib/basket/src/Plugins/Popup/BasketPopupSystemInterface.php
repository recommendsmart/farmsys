<?php

namespace Drupal\basket\Plugins\Popup;

/**
 * Provides an interface for all Basket Popup plugins.
 */
interface BasketPopupSystemInterface {

  /**
   * Opening a popup.
   */
  public function open(&$response, $title, $html, $options);

  /**
   * Closing a popup.
   */
  public function getCloseOnclick();

  /**
   * {@inheritdoc}
   */
  public function attached(&$attached);

}
