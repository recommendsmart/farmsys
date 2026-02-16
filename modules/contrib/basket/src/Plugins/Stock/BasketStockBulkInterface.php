<?php

namespace Drupal\basket\Plugins\Stock;

/**
 * Provides an interface for all Basket Stock plugins.
 */
interface BasketStockBulkInterface {

  /**
   * Return svg icon code.
   */
  public function getIcoContent();

  /**
   * Fill out the form with your fields.
   */
  public function getForm(&$form, $form_state);

  /**
   * Getting settings for further processing.
   */
  public function getBulkSettings($form_state);

  /**
   * Apply changes to a node.
   */
  public function processBulk($nid, $settings);

}
