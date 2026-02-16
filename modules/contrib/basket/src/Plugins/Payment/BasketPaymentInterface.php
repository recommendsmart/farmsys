<?php

namespace Drupal\basket\Plugins\Payment;

/**
 * Provides an interface for all Basket Payment plugins.
 */
interface BasketPaymentInterface {

  /**
   * SettingsFormAlter.
   *
   * Alter for making adjustments when creating a payment item.
   */
  public function settingsFormAlter(&$form, $form_state);

  /**
   * GetSettingsInfoList.
   *
   * Interpretation of the list of settings for pages with payment types
   * return [];.
   */
  public function getSettingsInfoList($tid);

  /**
   * CreatePayment.
   *
   * Creation of payment
   * return [
   * 'payID'            => 10,
   * 'redirectUrl'    => \Drupal::url('payment.routing')
   * ];.
   */
  public function createPayment($entity, $order);

  /**
   * Order update upon successful ordering by payment item settings.
   */
  public function updateOrderBySettings($pid, $Order);

  /**
   * LoadPayment.
   *
   * Downloading Payment Data
   * return [
   * 'payment'        => object,
   * 'isPay'            => TRUE/FALSE
   * ];.
   */
  public function loadPayment($id);

  /**
   * Alter page redirects for payment.
   */
  public function paymentFormAlter(&$form, $form_state, $payment);

  /**
   * BasketPaymentPages.
   *
   * Alter processing pages of interaction between
   * the payment system and the site
   * $pageType = callback/result/cancel.
   */
  public function basketPaymentPages($pageType);

}
