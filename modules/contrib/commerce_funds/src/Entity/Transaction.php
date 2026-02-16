<?php

namespace Drupal\commerce_funds\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\commerce_price\Entity\CurrencyInterface;
use Drupal\user\UserInterface;
use Drupal\Component\Utility\Crypt;

/**
 * Provides transaction entity.
 *
 * @ingroup commerce_funds
 *
 * @ContentEntityType(
 *   id = "commerce_funds_transaction",
 *   label = @Translation("Transaction"),
 *   label_collection = @Translation("Transactions"),
 *   label_singular = @Translation("transaction"),
 *   label_plural = @Translation("transactions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count transaction",
 *     plural = "@count transactions",
 *   ),
 *   bundle_label = @Translation("Transaction type"),
 *   handlers = {
 *     "event" = "Drupal\commerce_funds\Event\TransactionEvent",
 *     "views_data" = "Drupal\commerce_funds\FundsEntityViewsData",
 *   },
 *   base_table = "commerce_funds_transactions",
 *   admin_permission = "administer transactions",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "transaction_id",
 *     "bundle" = "type",
 *     "label" = "brut_amount",
 *     "uuid" = "uuid",
 *     "owner" = "issuer",
 *     "uid" = "issuer",
 *   },
 *   links = {
 *     "collection" = "/admin/commerce/funds/view-transactions",
 *   },
 *   bundle_entity_type = "commerce_funds_transaction_type",
 *   fieldable = FALSE,
 * )
 */
class Transaction extends ContentEntityBase implements TransactionInterface {

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->getEntityKey('owner')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('owner', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->getEntityKey('owner');
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('owner', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIssuer() {
    return $this->get('issuer')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setIssuer(UserInterface $account) {
    $this->set('issuer', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIssuerId() {
    return $this->get('issuer')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setIssuerId($uid) {
    $this->set('issuer', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipient() {
    return $this->get('recipient')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecipient(UserInterface $account) {
    $this->set('recipient', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipientId() {
    return $this->get('recipient')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecipientId($uid) {
    $this->set('recipient', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMethod() {
    return $this->get('method')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMethod($method) {
    $this->set('method', $method);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBrutAmount() {
    return $this->get('brut_amount')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setBrutAmount($brut_amount) {
    $this->set('brut_amount', $brut_amount);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNetAmount() {
    return $this->get('net_amount')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setNetAmount($net_amount) {
    $this->set('net_amount', $net_amount);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFee() {
    return $this->get('fee')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFee($fee) {
    $this->set('fee', $fee);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrency() {
    return $this->get('currency')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrency(CurrencyInterface $currency) {
    $this->set('currency', $currency->getCurrencyCode());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrencyCode() {
    return $this->get('currency')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrencyCode($currency_code) {
    $this->set('currency', $currency_code);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFromCurrency() {
    return $this->get('from_currency')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setFromCurrency(CurrencyInterface $currency) {
    $this->set('from_currency', $currency->getCurrencyCode());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFromCurrencyCode() {
    return $this->get('from_currency')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setFromCurrencyCode($currency_code) {
    $this->set('from_currency', $currency_code);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNotes() {
    return $this->get('notes')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setNotes($notes) {
    $this->set('notes', $notes);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHash() {
    return $this->get('hash')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setHash($hash) {
    $this->set('hash', $hash);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['issuer'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Issuer'))
      ->setDescription(t('The issuer id of the transaction.'))
      ->setRequired(TRUE)
      ->setDefaultValueCallback('Drupal\commerce_funds\Entity\Transaction::getCurrentUserId')
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('target_type', 'user');

    $fields['recipient'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Recipient'))
      ->setDescription(t('The recipient id of the transaction.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 2,
      ]);

    $fields['method'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment method'))
      ->setDescription(t('The transaction payment method.'))
      ->setRequired(TRUE)
      ->setDefaultValue('internal')
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('When the transaction has been initiated.'))
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 50,
      ]);

    $fields['brut_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Brut amount'))
      ->setDescription(t('The amount of the transaction before applying the fees.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'decimal',
        'weight' => 1,
      ])
      ->setSetting('display_description', TRUE);

    $fields['net_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Net amount'))
      ->setDescription(t('The total amount of the transaction.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['fee'] = BaseFieldDefinition::create('decimal')
      ->setSettings([
        'scale' => 3,
      ])
      ->setLabel(t('Fee'))
      ->setDescription(t('Fee applied to the transaction.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['currency'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Currency'))
      ->setDescription(t('The currency of the transaction.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_currency')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 2,
      ]);

    $fields['from_currency'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Converted currency'))
      ->setDescription(t('The currency before conversion.'))
      ->setCardinality(1)
      ->setSetting('target_type', 'commerce_currency')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 2,
      ]);

    $fields['status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Status'))
      ->setDescription(t('The current status of the transaction.'))
      ->setRequired(TRUE)
      ->setDefaultValue('Completed')
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hash'))
      ->setDescription(t('Unique transaction hash.'))
      ->setDefaultValueCallback('Drupal\commerce_funds\Entity\Transaction::hashGenerate')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE)
      ->setDefaultValue('');

    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notes'))
      ->setDescription(t('Notes of the issuer of the transaction.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 6,
        'rows' => 6,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'text_default',
        'weight' => 6,
      ]);

    return $fields;
  }

  /**
   * Default value callback for 'issuer' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * Generate a unique URL-safe hash.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return string
   *   A unique up to 4 * 12 character identifier.
   */
  public static function hashGenerate() {

    $hash = Crypt::randomBytesBase64(12);
    // Make sure hash is unique.
    if (\Drupal::database()->query("SELECT transaction_id FROM {commerce_funds_transactions} WHERE hash = :hash", [':hash' => $hash])->fetchObject()) {
      $hash = self::hashGenerate();
    }

    return $hash;
  }

}
