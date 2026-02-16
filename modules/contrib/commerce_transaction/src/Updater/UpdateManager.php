<?php

declare(strict_types=1);

namespace Drupal\commerce_transaction\Updater;

use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeInterface;
use Drupal\commerce_transaction\Entity\TransactionInterface;
use Drupal\commerce_transaction\MachineName\Field\Transaction as TransactionField;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityInterface;

use Psr\Log\LoggerInterface;

/**
 * The default transaction updater manager.
 */
class UpdateManager implements UpdateManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module's logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new Installer object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The module's logger.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerInterface $logger
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function update(TransactionInterface $transaction) {
    $payment_type = $transaction
      ->getPayment()
      ->getType();

    $this->paymentTypeSupportsUpdating($payment_type);

    $pending_states = $this->paymentTypePendingStates($payment_type);
    $this->validatePendingState(
      $transaction->getPayment(),
      'state',
      $pending_states['payment']
    );
    $this->validatePendingState(
      $transaction,
      TransactionField::REMOTE_STATE,
      $pending_states['transaction']
    );

    $this->doUpdateTransaction(
      $payment_type,
      $transaction
    );
  }

  /**
   * {@inheritdoc}
   */
  public function updateForPaymentType(
    PaymentTypeInterface $payment_type,
    array $options = []
  ) {
    $this->paymentTypeSupportsUpdating($payment_type);

    $options = array_merge(
      [
        'limit' => 50,
        'access_check' => TRUE,
      ],
      $options
    );

    $pending_states = $this->paymentTypePendingStates($payment_type);

    $payment_ids = $this->entityTypeManager
      ->getStorage('commerce_payment')
      ->getQuery()
      ->accessCheck($options['access_check'])
      ->condition('type', $payment_type->getPluginId(), '=')
      ->condition('state', $pending_states['payment'], 'IN')
      ->range(0, $options['limit'])
      ->sort('payment_id', 'ASC')
      ->execute();
    if (!$payment_ids) {
      return;
    }

    $storage = $this->entityTypeManager->getStorage('commerce_transaction');
    $transaction_ids = $storage
      ->getQuery()
      ->accessCheck($options['access_check'])
      ->condition('remote_state', $pending_states['transaction'], 'IN')
      ->condition('payment_id', $payment_ids, 'IN')
      ->range(0, $options['limit'])
      ->sort('id', 'ASC')
      ->execute();
    if (!$transaction_ids) {
      return;
    }

    $transactions = $storage->loadMultiple($transaction_ids);
    foreach ($transactions as $transaction) {
      // We catch and log errors so that we can proceed and update other
      // transactions. Otherwise all transactions could be prevented from
      // getting updated when a single transaction fails for some reason.
      try {
        $this->doUpdateTransaction($payment_type, $transaction);
      }
      catch (\Throwable $throwable) {
        $this->logger->error(
          'An error occurred while updating the transaction with ID "%transaction_id". Error type: "%error_type". Error message: @message.',
          [
            '%transaction_id' => $transaction->id(),
            '%error_type' => get_class($throwable),
            '@message' => $throwable->getMessage(),
          ]
        );
      }
    }
  }

  /**
   * Updates the given transaction.
   *
   * @param \Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeInterface $payment_type
   *   The transaction's parent payment type.
   * @param \Drupal\commerce_transaction\Entity\TransactionInterface $transaction
   *   The transaction to update.
   */
  protected function doUpdateTransaction(
    PaymentTypeInterface $payment_type,
    TransactionInterface $transaction
  ) {
    $payment_type->updateTransaction($transaction);
  }

  /**
   * Checks whether the given payment type supports updating transactions.
   *
   * @param \Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeInterface $payment_type
   *   The payment type.
   *
   * @throws \RuntimeException
   *   When the given payment type does not support updating transactions.
   */
  protected function paymentTypeSupportsUpdating(
    PaymentTypeInterface $payment_type
  ) {
    if ($payment_type instanceof SupportsTransactionUpdatingInterface) {
      return;
    }

    throw new \RuntimeException(sprintf(
      'The "%s" payment type does not support transaction updating.',
      $payment_type->getPluginId()
    ));
  }

  /**
   * Returns the payment/transaction states that are considered pending.
   *
   * @param \Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeInterface $payment_type
   *   The payment type.
   *
   * @throws \RuntimeException
   *   When the given payment type does not properly define the payment or
   *   transaction pending states.
   *
   * @see \Drupal\commerce_transaction\Updater\SupportsTransactionUpdatingInterface::transactionUpdaterPendingStates()
   */
  protected function paymentTypePendingStates(
    PaymentTypeInterface $payment_type
  ): array {
    $states = $payment_type->transactionUpdaterPendingStates();

    if (!isset($states['payment']) || !is_array($states['payment'])) {
      throw new \RuntimeException(sprintf(
        'No payment states defined for the transaction updater for the "%s" payment type.',
        $payment_type->getPluginId()
      ));
    }
    if (!isset($states['transaction']) || !is_array($states['transaction'])) {
      throw new \RuntimeException(sprintf(
        'No transaction states defined for the transaction updater for the "%s" payment type.',
        $payment_type->getPluginId()
      ));
    }

    return $states;
  }

  /**
   * Validates that the given entity is in a state considered as pending.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $field_name
   *   The name of the state field.
   * @param array $pending_states
   *   An array containing the state IDs that are considered to be pending.
   */
  protected function validatePendingState(
    EntityInterface $entity,
    string $field_name,
    array $pending_states
  ) {
    $field = $entity->get($field_name);
    if (!$field->isEmpty() && in_array($field->getId(), $pending_states)) {
      return;
    }

    throw new \RuntimeException(sprintf(
      'Requested to fetch updates for the "%s" entity with ID "%s" of type "%s" that is in the "%s" state which is not considered to be pending updates by the parent payment type.',
      $entity->getEntityTypeId(),
      $entity->id(),
      $entity->bundle(),
      $field->getId()
    ));
  }

}
