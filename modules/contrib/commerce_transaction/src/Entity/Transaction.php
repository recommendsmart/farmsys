<?php

declare(strict_types=1);

namespace Drupal\commerce_transaction\Entity;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_price\Price;
use Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The default class for the Transaction entity.
 *
 * @ContentEntityType(
 *   id = "commerce_transaction",
 *   label = @Translation("Payment transaction", context = "Commerce"),
 *   label_collection = @Translation("Payment transactions", context = "Commerce"),
 *   label_singular = @Translation("payment transaction", context = "Commerce"),
 *   label_plural = @Translation("payment transactions", context = "Commerce"),
 *   label_count = @PluralTranslation(
 *     singular = "@count payment transaction",
 *     plural = "@count payment transactions",
 *     context = "Commerce",
 *   ),
 *   bundle_label = @Translation("Payment transaction type", context = "Commerce"),
 *   bundle_entity_type = "commerce_transaction_type",
 *   handlers = {
 *     "storage" = "Drupal\commerce_transaction\Entity\Storage\Transaction",
 *   },
 *   base_table = "commerce_transaction",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type"
 *   },
 * )
 */
class Transaction extends ContentEntityBase implements TransactionInterface {

  /**
   * {@inheritdoc}
   */
  public function getPaymentId(): int {
    return $this->get('payment_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getPayment(): PaymentInterface {
    return $this->get('payment_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmount(): Price {
    if (!$this->get('amount')->isEmpty()) {
      return $this->get('amount')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setAmount(Price $amount): TransactionInterface {
    $this->set('amount', $amount);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteId(): ?string {
    return $this->get('remote_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRemoteId(string $remote_id): TransactionInterface {
    $this->set('remote_id', $remote_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteState(): StateItemInterface {
    return $this->get('remote_state')->first();
  }

  /**
   * {@inheritdoc}
   *
   * By default we follow a lightweight approach in the steps of the Commerce
   * Payment module i.e. we do not track the timestamps that the entity was
   * created/changed and by which user. That can be improved in the future as
   * the need arises.
   */
  public static function baseFieldDefinitions(
    EntityTypeInterface $entity_type
  ) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['payment_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Payment'))
      ->setDescription(new TranslatableMarkup('The parent payment.'))
      ->setSetting('target_type', 'commerce_payment')
      ->setReadOnly(TRUE);

    $fields['amount'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(new TranslatableMarkup('Amount'))
      ->setDescription(new TranslatableMarkup('The transaction amount.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['remote_id'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Remote ID'))
      ->setDescription(new TranslatableMarkup('The remote transaction ID.'))
      ->setRequired(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', TRUE);

    $fields['remote_state'] = BaseFieldDefinition::create('state')
      ->setLabel(new TranslatableMarkup('Remote state'))
      ->setDescription(new TranslatableMarkup('The remote transaction state.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setSetting(
        'workflow_callback',
        [
          '\Drupal\commerce_transaction\Entity\Transaction',
          'getRemoteWorkflowId',
        ]
      )
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Gets the workflow ID for the remote state field of the given transaction.
   *
   * @param \Drupal\commerce_transaction\Entity\TransactionInterface $transaction
   *   The transaction.
   *
   * @return string
   *   The workflow ID.
   */
  public static function getRemoteWorkflowId(
    TransactionInterface $transaction
  ) {
    return \Drupal::service('entity_type.manager')
      ->getStorage('commerce_transaction_type')
      ->load($transaction->bundle())
      ->getWorkflowId();
  }

}
