<?php

namespace Drupal\commerce_transaction\Updater;

use Drupal\commerce_transaction\Entity\TransactionInterface;

/**
 * The interface for payment type plugins that support updating transactions.
 */
interface SupportsTransactionUpdatingInterface {

  /**
   * Returns the states that are considered as pending updates.
   *
   * The update manager should only fetch updates for transactions that
   * themselves and their parent payments are considered to be pending. We
   * therefore need the payment type to inform the update manager which
   * states are considered pending.
   *
   * @return array
   *   An associative array containing the pending states. Array items should
   *   be:
   *   - payment: Contains the state IDs for the workflow of the payment state
   *     field that are considered as pending updates.
   *   - transaction: Contains the state IDs for the workflow of the transaction
   *     remote state field that are considered as pending updates.
   */
  public function transactionUpdaterPendingStates(): array;

  /**
   * Updates the given transaction.
   *
   * Implementations should normally be limited to fetching the updates for the
   * transaction from the remote payment gateway system and updating the
   * transaction entity only. To limit the scope of the payment type plugin and
   * to promote better structured code, any other actions - such as updating the
   * parent payment according to the updated transaction state - should be taken
   * in a event subscriber listening to the transaction entity state transition
   * events.
   */
  public function updateTransaction(TransactionInterface $transaction);

}
