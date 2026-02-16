<?php

declare(strict_types=1);

namespace Drupal\commerce_transaction\Entity\Storage;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_transaction\Entity\TransactionTypeInterface;
use Drupal\commerce_transaction\MachineName\Field\Transaction as TransactionField;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * The default class for the Transaction entity storage.
 */
class Transaction extends SqlContentEntityStorage implements
  TransactionInterface {

  /**
   * {@inheritdoc}
   */
  public function createForPayment(
    PaymentInterface $payment,
    array $values = []
  ) {
    $values[TransactionField::PAYMENT_ID] = $payment->id();
    return $this->create($values);
  }

  /**
   * {@inheritdoc}
   */
  public function loadPrincipalForPayment(
    PaymentInterface $payment,
    string $transaction_type_id
  ) {
    $transaction_type = $this->entityTypeManager
      ->getStorage('commerce_transaction_type')
      ->load($transaction_type_id);
    if ($transaction_type->getType() !== TransactionTypeInterface::TYPE_CHARGE) {
      throw new \RuntimeException(sprintf(
        'The requested transaction type "%s" is not of type "%s".',
        $transaction_type_id,
        TransactionTypeInterface::CHARGE
      ));
    }

    $transaction_ids = $this->getQuery()
      // We skip access check here to be aligned with how other entity loading
      // methods work.
      // See \Drupal\Core\Entity\EntityStorageBase::loadByProperties().
      ->accessCheck(FALSE)
      ->condition(TransactionField::PAYMENT_ID, $payment->id())
      ->condition(TransactionField::TYPE, $transaction_type_id)
      ->execute();
    if (!$transaction_ids) {
      throw new \RuntimeException(sprintf(
        'No principal transaction found for payment with ID "%s".',
        $payment->id()
      ));
    }
    if (count($transaction_ids) !== 1) {
      throw new \RuntimeException(sprintf(
        'Multiple principal transactions found for payment with ID "%s".',
        $payment->id()
      ));
    }

    return $this->load(current($transaction_ids));
  }

}
