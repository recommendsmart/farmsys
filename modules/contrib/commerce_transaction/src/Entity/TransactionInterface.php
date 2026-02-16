<?php

namespace Drupal\commerce_transaction\Entity;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_price\Price;
use Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides the interface for the Transaction entity.
 *
 * A Transaction entity is always associated with a payment. It is normally
 * either the initial charge, or one of potentially multiple refunds issued on
 * the initial charge. Since the Commerce Payment module only provides one
 * entity for what can usually be multiple records on the remote system (payment
 * gateway), Transaction entities can be used to track all transactions
 * individually.
 */
interface TransactionInterface extends ContentEntityInterface {

  /**
   * Returns the ID of the parent payment entity.
   *
   * @return int
   *   The payment ID.
   */
  public function getPaymentId(): int;

  /**
   * Returns the parent payment entity.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface
   *   The payment.
   */
  public function getPayment(): PaymentInterface;

  /**
   * Returns the transaction amount.
   *
   * @return \Drupal\commerce_price\Price
   *   The amount.
   */
  public function getAmount(): Price;

  /**
   * Sets the transaction amount.
   *
   * @param \Drupal\commerce_price\Price $amount
   *   The amount.
   *
   * @return $this
   */
  public function setAmount(Price $amount): TransactionInterface;

  /**
   * Returns the remote ID of the transaction.
   *
   * @return string|null
   *   The remote transaction ID, or NULL if the transaction has not been
   *   created in the remote yet.
   */
  public function getRemoteId(): ?string;

  /**
   * Sets the remote ID of the transaction.
   *
   * @param string $remote_id
   *   The remote ID.
   *
   * @return $this
   */
  public function setRemoteId(string $remote_id): TransactionInterface;

  /**
   * Returns the remote state of the transaction.
   *
   * @return \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   *   The remote transaction state.
   */
  public function getRemoteState(): StateItemInterface;

}
