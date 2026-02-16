<?php

declare(strict_types=1);

namespace Drupal\commerce_transaction\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * The default class for the Transaction Type entity.
 *
 * @ConfigEntityType(
 *   id = "commerce_transaction_type",
 *   label = @Translation("Payment transaction type", context = "Commerce"),
 *   label_collection = @Translation("Payment transaction types", context = "Commerce"),
 *   label_singular = @Translation("payment transaction type", context = "Commerce"),
 *   label_plural = @Translation("payment transaction types", context = "Commerce"),
 *   label_count = @PluralTranslation(
 *     singular = "@count payment transaction type",
 *     plural = "@count payment transaction types",
 *     context = "Commerce",
 *   ),
 *   config_prefix = "type",
 *   bundle_of = "commerce_transaction",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "uuid",
 *     "label",
 *     "description",
 *     "type",
 *     "workflow",
 *   },
 * )
 */
class TransactionType extends ConfigEntityBundleBase implements
  TransactionTypeInterface {

  /**
   * The machine name of the transaction type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the transaction type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of the transaction type.
   *
   * @var string
   */
  protected $description;

  /**
   * The type of the transaction i.e. charge or refund.
   *
   * @var string
   */
  protected $type;

  /**
   * The ID of the workflow that transactions of this type follow.
   *
   * @var string
   */
  protected $workflow;

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // The transaction type depends on the provider of the workflow.
    $workflow = \Drupal::service('plugin.manager.workflow')
      ->createInstance($this->getWorkflowId());
    $this->calculatePluginDependencies($workflow);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType(): string {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflowId(): string {
    return $this->workflow;
  }

  /**
   * {@inheritdoc}
   */
  public function setWorkflowId(string $workflow_id): TransactionTypeInterface {
    $this->workflow = $workflow_id;
    return $this;
  }

}
