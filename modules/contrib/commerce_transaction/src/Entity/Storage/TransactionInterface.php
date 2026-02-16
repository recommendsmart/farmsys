<?php

namespace Drupal\commerce_transaction\Entity\Storage;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;

/**
 * Provides the interface for the Transaction entity storage.
 */
interface TransactionInterface extends SqlEntityStorageInterface {

  /**
   * Creates a transaction for the given parent payment.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   */
  public function createForPayment(
    PaymentInterface $payment,
    array $values = []
  );

  /**
   * Loads the principal transaction for the given parent payment.
   *
   * The principal transaction for a payment is the initial charge
   * transaction. A payment could have potential multiple refund transactions
   * associated with it, but only one charge transaction.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   * @param string $transaction_type
   *   The expected type (bundle) of the transaction to load. It must be of type
   *   `\Drupal\commerce_transaction\Entity\TransactionTypeInterface::TYPE_CHARGE`.
   *
   * @return \Drupal\commerce_payment\Entity\TransactionInterface
   *   The transaction.
   *
   * @throws \RuntimeException
   *   When the given transaction type is not of type `charge`.
   * @throws \RuntimeException
   *   When no or multiple principal transactions are found.
   */
  public function loadPrincipalForPayment(
    PaymentInterface $payment,
    string $transaction_type
  );

}
