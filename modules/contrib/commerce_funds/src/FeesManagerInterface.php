<?php

namespace Drupal\commerce_funds;

use Drupal\commerce_order\Entity\Order;

/**
 * Fees Manager interface.
 */
interface FeesManagerInterface {

  /**
   * Calculate the fee apply to a deposit.
   *
   * @param Drupal\commerce_order\Entity\Order $order
   *   The order object.
   *
   * @return int
   *   Fee applied to the deposit.
   */
  public function calculateOrderFee(Order $order);

  /**
   * Apply fees to the order.
   *
   * Create a fee order item and add it to the order.
   *
   * @param Drupal\commerce_order\Entity\Order $order
   *   The order object.
   *
   * @return Drupal\commerce_order\Entity\Order
   *   The order with the fees applied to it.
   */
  public function applyFeeToOrder(Order $order);

  /**
   * Details fees applied to a payment gateway.
   *
   * @param string $payment_gateway
   *   The payment gateway id.
   * @param string $currency_code
   *   The currency_code.
   * @param string $type
   *   The transaction type.
   *
   * @return string
   *   Description of fees applied for the payment gateway.
   */
  public function printPaymentGatewayFees($payment_gateway, $currency_code, $type);

  /**
   * Calculate the fee apply to a transaction.
   *
   * @param int $brut_amount
   *   The transaction amount.
   * @param string $currency
   *   The transaction currency.
   * @param string $type
   *   The transaction type.
   *
   * @return array
   *   Fee applied to the transaction.
   */
  public function calculateTransactionFee($brut_amount, $currency, $type);

  /**
   * Display the fees applied for a transaction type.
   *
   * @param string $transaction_type
   *   Machine name of the transaction type.
   *
   * @return string
   *   Description of fees applied for the transaction.
   */
  public function printTransactionFees($transaction_type);

  /**
   * Convert a currency into another.
   *
   * @param int $amount
   *   The transaction amount.
   * @param string $currency_left
   *   The currency to be converted from.
   * @param string $currency_right
   *   The currency to convert into.
   *
   * @return array
   *   New amount and rate applied
   */
  public function convertCurrencyAmount($amount, $currency_left, $currency_right);

  /**
   * Display the converted amount.
   *
   * @param string $amount
   *   The transaction amount.
   * @param string $currency_left
   *   The currency to be converted from.
   * @param string $currency_right
   *   The currency to convert into.
   *
   * @return string
   *   New amount value after convertion.
   */
  public function printConvertedAmount(string $amount, string $currency_left, string $currency_right);

}
