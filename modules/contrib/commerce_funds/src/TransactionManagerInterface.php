<?php

namespace Drupal\commerce_funds;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_funds\Entity\TransactionInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Transaction manager interface.
 */
interface TransactionManagerInterface {

  /**
   * Add deposit amount to user balance.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The current order.
   */
  public function addDepositToBalance(OrderInterface $order);

  /**
   * Update balances.
   *
   * Perfom the transaction operations depending on the type.
   *
   * @param \Drupal\commerce_funds\Entity\TransactionInterface $transaction
   *   The current transaction.
   */
  public function performTransaction(TransactionInterface $transaction);

  /**
   * Add funds from balance.
   *
   * @param \Drupal\commerce_funds\Entity\TransactionInterface $transaction
   *   The transaction.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user.
   */
  public function addFundsToBalance(TransactionInterface $transaction, AccountInterface $account);

  /**
   * Remove Funds from balance.
   *
   * @param \Drupal\commerce_funds\Entity\TransactionInterface $transaction
   *   The transaction.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user.
   */
  public function removeFundsFromBalance(TransactionInterface $transaction, AccountInterface $account);

  /**
   * Update site balance.
   *
   * @param \Drupal\commerce_funds\Entity\TransactionInterface $transaction
   *   The transaction.
   */
  public function updateSiteBalance(TransactionInterface $transaction);

  /**
   * Load an account balance.
   *
   * Load unserialized balance from a user account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user account.
   *
   * @return array
   *   The user balance with each currency.
   */
  public function loadAccountBalance(AccountInterface $account);

  /**
   * Load global site balance.
   *
   * Load unserialized balance from admin user.
   *
   * @return array
   *   The site balance with each currency.
   */
  public function loadSiteBalance();

  /**
   * Load transaction from hash.
   *
   * @param string $hash
   *   The transaction hash.
   *
   * @return Drupal\commerce_funds\Entity\Transaction
   *   The transaction.
   */
  public function loadTransactionByHash($hash);

  /**
   * Get the transaction currency.
   *
   * @param int $transaction_id
   *   The transaction id.
   *
   * @return Drupal\commerce_price\Entity\Currency
   *   The transaction currency.
   */
  public function getTransactionCurrency($transaction_id);

  /**
   * Get the conversion initial currency.
   *
   * @param int $transaction_id
   *   The transaction id.
   *
   * @return Drupal\commerce_price\Entity\Currency
   *   The source currency of the conversion.
   */
  public function getConversionFromCurrency($transaction_id);

}
