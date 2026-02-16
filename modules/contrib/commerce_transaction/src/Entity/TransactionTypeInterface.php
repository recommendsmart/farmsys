<?php

namespace Drupal\commerce_transaction\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;

/**
 * Provides the interface for the Transaction Type entity.
 *
 * Commonly, there are two types of transactions that may be given different
 * names by different payment gateways: charge and refund. We allow creating
 * multiple bundles because each gateway may follow a different workflow for
 * its transactions.
 */
interface TransactionTypeInterface extends
  ConfigEntityInterface,
  EntityDescriptionInterface {

  /**
   * Indicates a charge transaction.
   */
  const TYPE_CHARGE = 'charge';

  /**
   * Indicates a refund transaction.
   */
  const TYPE_REFUND = 'refund';

  /**
   * Returns the type for transactions of this bundle.
   *
   * That is, `TYPE_CHARGE` or `TYPE_REFUND`.
   *
   * @return string
   *   The transaction type.
   */
  public function getType(): string;

  /**
   * Returns the workflow ID for transaction of this bundle.
   *
   * @return string
   *   The transaction type workflow ID.
   */
  public function getWorkflowId(): string;

  /**
   * Sets the workflow ID for the transaction type.
   *
   * @param string $workflow_id
   *   The workflow ID.
   *
   * @return $this
   */
  public function setWorkflowId(string $workflow_id): TransactionTypeInterface;

}
