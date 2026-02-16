<?php

namespace Drupal\commerce_funds\Services;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_funds\Entity\Transaction;
use Drupal\commerce_funds\Entity\TransactionInterface;
use Drupal\commerce_funds\Exception\TransactionDeniedException;

/**
 * Transaction manager class.
 */
class TransactionManager {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The db connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $connection, AccountProxy $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('current_user')
    );
  }

  /**
   * Add deposit amount to user balance.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The current order.
   */
  public function addDepositToBalance(OrderInterface $order) {
    $deposit_amount = $order->getItems()[0]->getTotalPrice()->getNumber();
    $deposit_currency_code = $order->getItems()[0]->getTotalPrice()->getCurrencyCode();
    $total_paid = $order->getTotalPrice()->getNumber();
    $fee_applied = Calculator::subtract($total_paid, $deposit_amount, 2);
    $payment_method = $order->get('payment_gateway')->getValue()[0]['target_id'];

    // Defines transaction and save it to db.
    $transaction = Transaction::create([
      'issuer' => $order->getCustomerId(),
      'recipient' => $order->getCustomerId(),
      'type' => 'deposit',
      'method' => $payment_method,
      'brut_amount' => $deposit_amount,
      'net_amount' => $total_paid,
      'fee' => $fee_applied,
      'currency' => $deposit_currency_code,
      'status' => 'Completed',
      'notes' => [
        'value' => $this->t('Deposit of @amount (@currency)', [
          '@amount' => number_format($deposit_amount, 2, '.', ','),
          '@currency' => $deposit_currency_code,
        ]),
        'format' => 'basic_html',
      ],
    ]);
    $transaction->save();
    // Update account balance.
    $this->performTransaction($transaction);
  }

  /**
   * Update balances.
   *
   * Perfom the transaction operations depending on the type.
   *
   * @param \Drupal\commerce_funds\Entity\TransactionInterface $transaction
   *   The current transaction.
   */
  public function performTransaction(TransactionInterface $transaction) {

    $type = $transaction->bundle();
    $currentUser = $this->currentUser;

    if ($type == 'deposit' && $currentUser->hasPermission('deposit funds')) {
      $this->addFundsToBalance($transaction, $transaction->getIssuer());
      $this->updateSiteBalance($transaction);
    }

    elseif ($type == 'transfer' && $currentUser->hasPermission('transfer funds') || $type == 'payment' && $currentUser->hasPermission('deposit funds')) {
      $this->addFundsToBalance($transaction, $transaction->getRecipient());
      $this->removeFundsFromBalance($transaction, $transaction->getIssuer());
      $this->updateSiteBalance($transaction);
    }

    elseif ($type == 'escrow' && $currentUser->hasPermission('create escrow payment')) {
      $this->removeFundsFromBalance($transaction, $transaction->getIssuer());
    }

    elseif ($type == 'withdrawal_request' && $currentUser->hasPermission('withdraw funds')) {
      $this->removeFundsFromBalance($transaction, $transaction->getIssuer());
      $this->updateSiteBalance($transaction);
    }

    elseif ($type == 'conversion' && $currentUser->hasPermission('convert currencies')) {
      $this->removeFundsFromBalance($transaction, $transaction->getIssuer());
      $this->addFundsToBalance($transaction, $transaction->getRecipient());
    }

    else {
      throw new TransactionDeniedException("Transaction permission denied: " . $transaction->bundle());
    }

  }

  /**
   * Add funds from balance.
   *
   * @param \Drupal\commerce_funds\Entity\TransactionInterface $transaction
   *   The current transaction.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   */
  public function addFundsToBalance(TransactionInterface $transaction, AccountInterface $account) {
    $brut_amount = $transaction->getBrutAmount();
    $currency_code = $transaction->getCurrencyCode();

    // Cover case where it's an escrow cancelled.
    if ($transaction->bundle() == "escrow" && $account->id() == $transaction->getIssuerId()) {
      $brut_amount = $transaction->getNetAmount();
    }

    // Cover conversions and payments.
    if ($transaction->bundle() == "conversion" || $transaction->bundle() == "payment") {
      $brut_amount = $transaction->getNetAmount();
    }

    $balance = $this->loadAccountBalance($account);
    $balance[$currency_code] = isset($balance[$currency_code]) ? $balance[$currency_code] : 0;
    $balance[$currency_code] = Calculator::add((string) $brut_amount, (string) $balance[$currency_code], 2);

    // Update the user balance.
    $uid = $account->hasPermission('administer transactions') ? 1 : $account->id();
    $this->connection->merge('commerce_funds_user_funds')
      ->insertFields([
        'uid' => $uid,
        'balance' => serialize($balance),
      ])
      ->updateFields([
        'balance' => serialize($balance),
      ])
      ->key(['uid' => $uid])
      ->execute();
  }

  /**
   * Remove Funds from balance.
   *
   * @param \Drupal\commerce_funds\Entity\TransactionInterface $transaction
   *   The current transaction.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   */
  public function removeFundsFromBalance(TransactionInterface $transaction, AccountInterface $account) {
    $net_amount = $transaction->getNetAmount();
    $currency_code = $transaction->getCurrencyCode();

    if ($transaction->bundle() == 'conversion') {
      $currency_code = $transaction->getFromCurrencyCode();
      $net_amount = $transaction->getBrutAmount();
    }

    $balance = $this->loadAccountBalance($account);
    $balance[$currency_code] = isset($balance[$currency_code]) ? $balance[$currency_code] : 0;
    $balance[$currency_code] = Calculator::subtract((string) $balance[$currency_code], (string) $net_amount, 2);

    // Update the user balance.
    $uid = $account->hasPermission('administer transactions') ? 1 : $account->id();
    $this->connection->merge('commerce_funds_user_funds')
      ->insertFields([
        'uid' => $uid,
        'balance' => serialize($balance),
      ])
      ->updateFields([
        'balance' => serialize($balance),
      ])
      ->key(['uid' => $uid])
      ->execute();
  }

  /**
   * Update site balance.
   *
   * @param \Drupal\commerce_funds\Entity\TransactionInterface $transaction
   *   The current transaction.
   */
  public function updateSiteBalance(TransactionInterface $transaction) {
    $currency_code = $transaction->getCurrencyCode();
    $site_balance = $this->loadSiteBalance();

    $site_balance[$currency_code] = isset($site_balance[$currency_code]) ? $site_balance[$currency_code] : 0;
    $site_balance[$currency_code] = Calculator::add((string) $transaction->getFee(), (string) $site_balance[$currency_code], 2);

    // Update site balance.
    $this->connection->merge('commerce_funds_user_funds')
      ->key(['uid' => 1])
      ->updateFields([
        'balance' => serialize($site_balance),
      ])
      ->execute();
  }

  /**
   * Load an account balance.
   *
   * Load unserialized balance from a user account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user account.
   *
   * @return array
   *   The user balance with each currency.
   */
  public function loadAccountBalance(AccountInterface $account) {
    // Load site balance if user can administer transactions.
    if ($account->hasPermission('administer transactions')) {
      return $this->loadSiteBalance($account);
    }
    // Check if issuer balance exists.
    $balance_exist = $this->connection->query("SELECT * FROM commerce_funds_user_funds WHERE uid = :uid", [
      ':uid' => $account->id(),
    ])->fetchObject();

    // Unserialize balance.
    $balance = $balance_exist ? unserialize($balance_exist->balance) : [];

    return $balance;
  }

  /**
   * Load global site balance.
   *
   * Load unserialized balance from admin user.
   *
   * @return array
   *   The site balance with each currency.
   */
  public function loadSiteBalance() {
    // Check if issuer balance exists.
    $balance_exist = $this->connection->query("SELECT * FROM commerce_funds_user_funds WHERE uid = :uid", [
      ':uid' => 1,
    ])->fetchObject();

    // Unserialize balance.
    $balance = $balance_exist ? unserialize($balance_exist->balance) : [];

    return $balance;
  }

  /**
   * Get the transaction currency.
   *
   * @param int $transaction_id
   *   The transaction id.
   *
   * @return Drupal\commerce_price\Entity\Currency
   *   The transaction currency.
   */
  public function getTransactionCurrency($transaction_id) {

    $currency = Transaction::load($transaction_id)->getCurrency();

    return $currency;
  }

  /**
   * Get the conversion initial currency.
   *
   * @param int $transaction_id
   *   The transaction id.
   *
   * @return Drupal\commerce_price\Entity\Currency
   *   The source currency of the conversion.
   */
  public function getConversionFromCurrency($transaction_id) {

    $currency = Transaction::load($transaction_id)->getFromCurrency();

    return $currency;
  }

}
