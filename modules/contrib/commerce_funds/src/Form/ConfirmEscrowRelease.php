<?php

namespace Drupal\commerce_funds\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_funds\Entity\Transaction;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a confirmation form to release an escrow payment.
 */
class ConfirmEscrowRelease extends ConfirmFormBase {

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The token utility.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Class constructor.
   */
  public function __construct(AccountProxy $current_user, MailManagerInterface $mail_manager, Token $token, MessengerInterface $messenger) {
    $this->currentUser = $current_user;
    $this->mailManager = $mail_manager;
    $this->token = $token;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('plugin.manager.mail'),
      $container->get('token'),
      $container->get('messenger')
    );
  }

  /**
   * The transaction.
   *
   * @var \Drupal\commerce_funds\Entity\Transaction
   */
  protected $transaction;

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "confirm_escrow_release";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('view.commerce_funds_user_transactions.incoming_escrow_payments');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to release that escrow payment?');
  }

  /**
   * Check if the user is allowed to perform an escrow operation.
   *
   * @param \Drupal\commerce_funds\Entity\Transaction $transaction
   *   The transaction id to check permissions on.
   *
   * @return bool
   *   User is allowed or not.
   */
  protected function isUserAllowed(Transaction $transaction) {
    $uid = $this->currentUser->id();
    $query = \Drupal::request()->get('action');

    if ($transaction->getStatus() !== "Completed") {
      if ($query == "cancel-escrow") {
        if ($uid == $transaction->getIssuerId() || $uid == $transaction->getRecipientId()) {
          return TRUE;
        }
      }
      if ($query == "release-escrow" && $uid == $transaction->getIssuerId()) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $transaction_hash = NULL) {
    $transaction = $this->transaction = \Drupal::service('commerce_funds.transaction_manager')->loadTransactionByHash($transaction_hash);
    // Check if the user is allowed to perform the operation.
    if (!empty($transaction) && $this->isUserAllowed($transaction)) {
      return parent::buildForm($form, $form_state);
    }
    else {
      throw new NotFoundHttpException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $transaction = $this->transaction;
    $currency_code = $transaction->getCurrency()->getCurrencycode();
    $symbol = $transaction->getCurrency()->getSymbol();
    // Make sure we just have two decimal.
    $fee = substr($transaction->getFee(), 0, -1);
    $config = $this->config('commerce_funds.settings');

    $issuer = $transaction->getIssuer();
    $recipient = $transaction->getRecipient();

    // Load necessary parameters for email.
    $mailManager = $this->mailManager;
    $token = $this->token;
    $langcode = $this->config('system.site')->get('langcode');

    // To recipient.
    if ($config->get('mail_escrow_released_recipient')['activated']) {
      $balance = \Drupal::service('commerce_funds.transaction_manager')->loadAccountBalance($recipient);
      $params = [
        'id' => 'escrow_released_recipient',
        'subject' => $token->replace($config->get('mail_escrow_released_recipient')['subject'], ['commerce_funds_transaction' => $transaction]),
        'body' => $token->replace($config->get('mail_escrow_released_recipient')['body']['value'], [
          'commerce_funds_transaction' => $transaction,
          'commerce_funds_balance' => $balance,
          'commerce_funds_balance_uid' => $recipient->id(),
        ]),
      ];
      $mailManager->mail('commerce_funds', 'commerce_funds_transaction', $recipient->getEmail(), $langcode, $params, NULL, TRUE);
    }

    // To issuer.
    if ($config->get('mail_escrow_released_issuer')['activated']) {
      $balance = \Drupal::service('commerce_funds.transaction_manager')->loadAccountBalance($issuer);
      $params = [
        'id' => 'escrow_released_issuer',
        'subject' => $token->replace($config->get('mail_escrow_released_issuer')['subject'], ['commerce_funds_transaction' => $transaction]),
        'body' => $token->replace($config->get('mail_escrow_released_issuer')['body']['value'], [
          'commerce_funds_transaction' => $transaction,
          'commerce_funds_balance' => $balance,
          'commerce_funds_balance_uid' => $issuer->id(),
        ]),
      ];
      $mailManager->mail('commerce_funds', 'commerce_funds_transaction', $issuer->getEmail(), $langcode, $params, NULL, TRUE);
    }

    // Set a confirmation message to user.
    if (!Calculator::compare($fee, 0)) {
      $this->messenger->addMessage($this->t('You have transfered @amount (@currency) to @user.', [
        '@amount' => $symbol . $transaction->getBrutAmount(),
        '@currency' => $currency_code,
        '@user' => $recipient->getAccountName(),
      ]), 'status');
    }
    else {
      $this->messenger->addMessage($this->t('You have transfered @amount (@currency) to @user with a commission of %commission.', [
        '@amount' => $symbol . $transaction->getBrutAmount(),
        '@currency' => $currency_code,
        '@user' => $recipient->getAccountName(),
        '%commission' => $symbol . $fee,
      ]), 'status');
    }

    // Release escrow payment.
    \Drupal::service('commerce_funds.transaction_manager')->addFundsToBalance($transaction, $recipient);

    // Update site balance.
    \Drupal::service('commerce_funds.transaction_manager')->updateSiteBalance($transaction);

    // Update transaction.
    $transaction->setStatus('Completed');
    $transaction->save();

    // Set redirection.
    $form_state->setRedirect('view.commerce_funds_user_transactions.incoming_escrow_payments');
  }

}
