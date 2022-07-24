<?php

namespace Drupal\basket\Plugins\Delivery;

/**
 * Provides an interface for all Basket Delivery plugins.
 */
interface BasketDeliveryInterface {

  /**
   * Get #parents.
   */
  public function basketFieldParents();

  /**
   * Embedding the form in the delivery field.
   */
  public function basketFormAlter(&$form, $form_state);

  /**
   * Deleting delivery data.
   */
  public function basketDelete($entity, $entity_delete);

  /**
   * Saving delivery data.
   */
  public function basketSave($entity, $form_state);

  /**
   * Loading delivery data.
   */
  public function basketLoad($form_state);

  /**
   * Get shipping address.
   */
  public function basketGetAddress($entity);

  /**
   * Alter shipping cost.
   */
  public function deliverySumAlter(&$info);

}
