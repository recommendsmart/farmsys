<?php

namespace Drupal\commerce_transaction\Updater;

use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeInterface;
use Drupal\commerce_transaction\Entity\TransactionInterface;

/**
 * Provides the interface for the transaction update manager.
 *
 * The transaction update manager facilitates fetching updates for transactions
 * from the remote payment gateway. It can be called in Cron hooks and in Drush
 * commands so that payment gateway modules implementing transactions do not
 * have to implement an update system themselves.
 */
interface UpdateManagerInterface {

  /**
   * Updates the given transaction.
   *
   * @param \Drupal\commerce_transaction\Entity\TransactionInterface $transaction
   *   The transaction to fetch the update for.
   *
   * @throws \RuntimeException
   *   When the parent payment type does not support updating transactions.
   * @throws \RuntimeException
   *   When the transaction or its parent payment is not in a state considered
   *   to be pending updates.
   */
  public function update(TransactionInterface $transaction);

  /**
   * Updates transactions for the given payment type.
   *
   * Only transactions that are pending will be updated. See
   * \Drupal\commerce_transaction\Updater\SupportsTransactionUpdatingInterface::transactionUpdaterPendingStates().
   *
   * @param \Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeInterface $payment_type
   *   The payment type to fetch the transaction updates for.
   * @param array $options
   *   An associative array of options. Currently supported options are:
   *   - limit (int, optional, defaults to 50): The maximum number of
   *     transactions to update.
   *   - access_check (bool, optional, defaults to TRUE): Whether to check
   *     access when querying for pending transactions. It should be set to TRUE
   *     when calling from Cron or Drush e.g. for regularly updating pending
   *     transactions fetching; these are executed as the anonymous users and
   *     there could be permission issues that would prevent the
   *     transations/payments from being loaded.
   *
   * @throws \RuntimeException
   *   When the parent payment type does not support updating transactions.
   * @throws \RuntimeException
   *   When the given payment type does not properly define the payment or
   *   transaction pending states.
   */
  public function updateForPaymentType(
    PaymentTypeInterface $payment_type,
    array $options = []
  );

}
