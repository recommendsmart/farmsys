<?php

namespace Drupal\commerce_funds\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\commerce_funds\Entity\Transaction;
use Drupal\commerce_funds\TransactionManagerInterface;
use Drupal\commerce_funds\FeesManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to transfer money to another user account.
 */
class FundsTransfer extends FormBase {

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Defines variables to be used later.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * The transaction manager.
   *
   * @var \Drupal\commerce_funds\TransactionManagerInterface
   */
  protected $transactionManager;

  /**
   * The fees manager.
   *
   * @var \Drupal\commerce_funds\FeesManagerInterface
   */
  protected $feesManager;

  /**
   * Class constructor.
   */
  public function __construct(AccountProxy $current_user, EntityTypeManagerInterface $entity_type_manager, MailManagerInterface $mail_manager, Token $token, MessengerInterface $messenger, TransactionManagerInterface $transaction_manager, FeesManagerInterface $fees_manager) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->mailManager = $mail_manager;
    $this->token = $token;
    $this->messenger = $messenger;
    $this->transactionManager = $transaction_manager;
    $this->feesManager = $fees_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('current_user'),
    $container->get('entity_type.manager'),
    $container->get('plugin.manager.mail'),
    $container->get('token'),
    $container->get('messenger'),
    $container->get('commerce_funds.transaction_manager'),
    $container->get('commerce_funds.fees_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_funds_transfer_funds';
  }

  /**
   * {@inheritdoc}
   *
   * Https://www.drupal.org/docs/8/api/form-api/configformbase-with-simple-configuration-api.
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_funds.transfer',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $currencies = $this->entityTypeManager->getStorage('commerce_currency')->loadMultiple();
    $currencyCodes = [];
    foreach ($currencies as $currency) {
      $currency_code = $currency->getCurrencyCode();
      $currencyCodes[$currency_code] = $currency_code;
    }
    $fees_description = $this->feesManager->printTransactionFees('transfer');

    $form['amount'] = [
      '#type' => 'number',
      '#min' => 0.0,
      '#title' => $this->t('Transfer Amount'),
      '#description' => $fees_description,
      '#step' => 0.01,
      '#default_value' => 0.0,
      '#size' => 30,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Currency'),
      '#description' => $this->t('Select the currency you want to transfer.'),
      '#options' => $currencyCodes,
    ];

    $form['username'] = [
      '#id' => 'commerce-funds-transfer-to',
      '#title' => $this->t('Transfer To'),
      '#description' => $this->t('Please enter a username.'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#required' => TRUE,
      '#size' => 30,
      '#maxlength' => 128,
      '#selection_settings' => [
        'include_anonymous' => FALSE,
      ],
    ];

    $form['notes'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Notes'),
      '#description' => $this->t('Eventually add a message to the recipient.'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Transfer funds'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $amount = $form_state->getValue('amount');
    $currency = $form_state->getValue('currency');
    $fee_applied = $this->feesManager->calculateTransactionFee($amount, $currency, 'transfer');

    $issuer = $this->currentUser;
    $issuer_balance = $this->transactionManager->loadAccountBalance($issuer->getAccount(), $currency);
    $currency_balance = isset($issuer_balance[$currency]) ? $issuer_balance[$currency] : 0;
    // Error if the user doesn't have enough money to cover the transfer + fee.
    if ($currency_balance < $fee_applied['net_amount']) {
      if (!$fee_applied['fee']) {
        $form_state->setErrorByName('amount', $this->t("You don't have enough funds to cover this transfer."));
      }
      if ($fee_applied['fee']) {
        $form_state->setErrorByName('amount', $this->t("You don't have enough funds to cover this transfer.<br>
        The commission applied is %commission (@currency).", [
          '%commission' => $fee_applied['fee'],
          '@currency' => $currency,
        ]));
      }
    }

    // Error if user try to send money to itself.
    $recipient = $this->entityTypeManager->getStorage('user')->load($form_state->getValue('username'));
    if ($issuer->id() == $recipient->id()) {
      $form_state->setErrorByName('username', $this->t("Operation impossible. You can't transfer money to yourself."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $amount = $form_state->getValue('amount');
    $currency = $form_state->getValue('currency');
    $fee_applied = $this->feesManager->calculateTransactionFee($amount, $currency, 'transfer');
    $config = $this->config('commerce_funds.settings');

    $issuer = $this->currentUser;
    $recipient = $this->entityTypeManager->getStorage('user')->load($form_state->getValue('username'));

    $transaction = Transaction::create([
      'issuer' => $issuer->id(),
      'recipient' => $recipient->id(),
      'type' => 'transfer',
      'method' => 'internal',
      'brut_amount' => $amount,
      'net_amount' => $fee_applied['net_amount'],
      'fee' => $fee_applied['fee'],
      'currency' => $currency,
      'status' => 'Completed',
      'notes' => [
        'value' => $form_state->getValue('notes')['value'],
        'format' => $form_state->getValue('notes')['format'],
      ],
    ]);
    $transaction->save();

    $this->transactionManager->performTransaction($transaction);

    // Load necessary parameters for email.
    $mailManager = $this->mailManager;
    $token = $this->token;
    $langcode = $this->config('system.site')->get('langcode');

    // To recipient.
    if ($config->get('mail_transfer_recipient')['activated']) {
      $balance = $this->transactionManager->loadAccountBalance($recipient);
      $params = [
        'id' => 'transfer_recipient',
        'subject' => $token->replace($config->get('mail_transfer_recipient')['subject'], ['commerce_funds_transaction' => $transaction]),
        'body' => $token->replace($config->get('mail_transfer_recipient')['body']['value'], [
          'commerce_funds_transaction' => $transaction,
          'commerce_funds_balance' => $balance,
          'commerce_funds_balance_uid' => $recipient->id(),
        ]),
      ];
      $mailManager->mail('commerce_funds', 'commerce_funds_transaction', $recipient->getEmail(), $langcode, $params, NULL, TRUE);
    }

    // To issuer.
    if ($config->get('mail_transfer_issuer')['activated']) {
      $balance = $this->transactionManager->loadAccountBalance($issuer);
      $params = [
        'id' => 'transfer_issuer',
        'subject' => $token->replace($config->get('mail_transfer_issuer')['subject'], ['commerce_funds_transaction' => $transaction]),
        'body' => $token->replace($config->get('mail_transfer_issuer')['body']['value'], [
          'commerce_funds_transaction' => $transaction,
          'commerce_funds_balance' => $balance,
          'commerce_funds_balance_uid' => $issuer->id(),
        ]),
      ];
      $mailManager->mail('commerce_funds', 'commerce_funds_transaction', $issuer->getEmail(), $langcode, $params, NULL, TRUE);
    }

    // Set a confirmation message to user.
    if (!$fee_applied['fee']) {
      $no_fee_msg = $this->t('You have transfered @amount @currency to @user', [
        '@amount' => $amount,
        '@currency' => $currency,
        '@user' => $recipient->getAccountName(),
      ]);
      $this->messenger->addMessage($no_fee_msg, 'status');
    }
    if ($fee_applied['fee']) {
      $fee_msg = $this->t('You have transfered @amount @currency to @user with a commission of %commission @currency', [
        '@amount' => $amount,
        '@currency' => $currency,
        '@user' => $recipient->getAccountName(),
        '%commission' => $fee_applied['fee'],
      ]);
      $this->messenger->addMessage($fee_msg, 'status');
    }
  }

}
