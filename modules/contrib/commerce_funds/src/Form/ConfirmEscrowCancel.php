<?php

namespace Drupal\commerce_funds\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\commerce_funds\Entity\Transaction;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a confirmation form to release an escrow payment.
 */
class ConfirmEscrowCancel extends ConfirmFormBase {

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
    return "confirm_escrow_cancel";
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
    return $this->t('Are you sure you want to cancel that escrow payment?');
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
    $symbol = $transaction->getCurrency()->getSymbol();
    $config = $this->config('commerce_funds.settings');

    $issuer = $transaction->getIssuer();
    $recipient = $transaction->getRecipient();

    // Cancel escrow payment.
    \Drupal::service('commerce_funds.transaction_manager')->addFundsToBalance($transaction, $issuer);

    // Update transaction.
    $transaction->setStatus('Cancelled');
    $transaction->save();

    // Load necessary parameters for email.
    $mailManager = $this->mailManager;
    $token = $this->token;
    $langcode = $this->config('system.site')->get('langcode');

    // The user who canceled the escrow is the issuer.
    if ($this->currentUser->id() == $transaction->getIssuerId()) {
      // To recipient.
      if ($config->get('mail_escrow_canceled_by_issuer_recipient')['activated']) {
        $balance = \Drupal::service('commerce_funds.transaction_manager')->loadAccountBalance($recipient);
        $params = [
          'id' => 'escrow_canceled_by_issuer_recipient',
          'subject' => $token->replace($config->get('mail_escrow_canceled_by_issuer_recipient')['subject'], ['commerce_funds_transaction' => $transaction]),
          'body' => $token->replace($config->get('mail_escrow_canceled_by_issuer_recipient')['body']['value'], [
            'commerce_funds_transaction' => $transaction,
            'commerce_funds_balance' => $balance,
            'commerce_funds_balance_uid' => $recipient->id(),
          ]),
        ];
        $mailManager->mail('commerce_funds', 'commerce_funds_transaction', $recipient->getEmail(), $langcode, $params, NULL, TRUE);
      }

      // To issuer.
      if ($config->get('mail_escrow_canceled_by_issuer_issuer')['activated']) {
        $balance = \Drupal::service('commerce_funds.transaction_manager')->loadAccountBalance($issuer);
        $params = [
          'id' => 'escrow_canceled_by_issuer_issuer',
          'subject' => $token->replace($config->get('mail_escrow_canceled_by_issuer_issuer')['subject'], ['commerce_funds_transaction' => $transaction]),
          'body' => $token->replace($config->get('mail_escrow_canceled_by_issuer_issuer')['body']['value'], [
            'commerce_funds_transaction' => $transaction,
            'commerce_funds_balance' => $balance,
            'commerce_funds_balance_uid' => $issuer->id(),
          ]),
        ];
        $mailManager->mail('commerce_funds', 'commerce_funds_transaction', $issuer->getEmail(), $langcode, $params, NULL, TRUE);
      }

      // Set a confirmation message to issuer.
      $this->messenger->addMessage($this->t('Escrow payment of @amount to @user has been cancelled.', [
        '@amount' => $symbol . $transaction->getBrutAmount(),
        '@user' => $recipient->getAccountName(),
      ]), 'status');
    }

    // The user who canceled the escrow is the recipient.
    if ($this->currentUser->id() == $transaction->getRecipientId()) {
      // Send an HTML email to the recipient of the escrow payment.
      // To recipient.
      if ($config->get('mail_escrow_canceled_by_recipient_recipient')['activated']) {
        $balance = \Drupal::service('commerce_funds.transaction_manager')->loadAccountBalance($recipient);
        $params = [
          'id' => 'escrow_canceled_by_recipient_recipient',
          'subject' => $token->replace($config->get('mail_escrow_canceled_by_recipient_recipient')['subject'], ['commerce_funds_transaction' => $transaction]),
          'body' => $token->replace($config->get('mail_escrow_canceled_by_recipient_recipient')['body']['value'], [
            'commerce_funds_transaction' => $transaction,
            'commerce_funds_balance' => $balance,
            'commerce_funds_balance_uid' => $recipient->id(),
          ]),
        ];
        $mailManager->mail('commerce_funds', 'commerce_funds_transaction', $recipient->getEmail(), $langcode, $params, NULL, TRUE);
      }

      // To issuer.
      if ($config->get('mail_escrow_canceled_by_recipient_issuer')['activated']) {
        $balance = \Drupal::service('commerce_funds.transaction_manager')->loadAccountBalance($issuer);
        $params = [
          'id' => 'escrow_canceled_by_recipient_issuer',
          'subject' => $token->replace($config->get('mail_escrow_canceled_by_recipient_issuer')['subject'], ['commerce_funds_transaction' => $transaction]),
          'body' => $token->replace($config->get('mail_escrow_canceled_by_recipient_issuer')['body']['value'], [
            'commerce_funds_transaction' => $transaction,
            'commerce_funds_balance' => $balance,
            'commerce_funds_balance_uid' => $issuer->id(),
          ]),
        ];
        $mailManager->mail('commerce_funds', 'commerce_funds_transaction', $issuer->getEmail(), $langcode, $params, NULL, TRUE);
      }

      // Set a confirmation message to recipient.
      $this->messenger->addMessage($this->t('Escrow payment of @amount from @user has been cancelled.', [
        '@amount' => $symbol . $transaction->getBrutAmount(),
        '@user' => $issuer->getAccountName(),
      ]), 'status');
    }

    // Set redirection.
    $form_state->setRedirect('view.commerce_funds_user_transactions.incoming_escrow_payments');
  }

}
