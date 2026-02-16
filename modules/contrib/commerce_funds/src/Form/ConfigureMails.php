<?php

namespace Drupal\commerce_funds\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\token\TreeBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to configure the mails sent by transactions.
 */
class ConfigureMails extends ConfigFormBase {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The token tree builder.
   *
   * @var \Drupal\token\TreeBuilderInterface
   */
  protected $treeBuilder;

  /**
   * Class constructor.
   */
  public function __construct(MessengerInterface $messenger, ModuleHandler $module_handler, TreeBuilderInterface $tree_builder) {
    $this->messenger = $messenger;
    $this->moduleHandler = $module_handler;
    $this->treeBuilder = $tree_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('module_handler'),
      $container->get('token.tree_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_funds_configure_mails';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_funds.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_funds.settings');

    $form['#tree'] = TRUE;
    $form['mail_markup_welcome'] = [
      '#markup' => $this->t('Enable and customize emails sent when a transaction is triggerd.
      <ul>
      <li>By default, emails are sent in plain text. To send HTML emails you can use the <a href="@mimemail-url" target="_blank">Mime Mail</a> module.</li>
      </ul>', [
        '@mimemail-url' => 'https://www.drupal.org/project/mimemail',
      ]),
    ];

    $message_types = [
      'transfer_issuer' => $this->t('Transfer (issuer)'),
      'transfer_recipient' => $this->t('Transfer (recipient)'),
      'escrow_created_issuer' => $this->t('Escrow created (issuer)'),
      'escrow_created_recipient' => $this->t('Escrow created (recipient)'),
      'escrow_canceled_by_issuer_issuer' => $this->t('Escrow canceled by issuer (issuer)'),
      'escrow_canceled_by_issuer_recipient' => $this->t('Escrow canceled by issuer (recipient)'),
      'escrow_canceled_by_recipient_issuer' => $this->t('Escrow canceled by recipient (issuer)'),
      'escrow_canceled_by_recipient_recipient' => $this->t('Escrow canceled by recipient (recipient)'),
      'escrow_released_issuer' => $this->t('Escrow released (issuer)'),
      'escrow_released_recipient' => $this->t('Escrow released (recipient)'),
      'withdrawal_declined' => $this->t('Withdrawal request declined (issuer)'),
      'withdrawal_approved' => $this->t('Withdrawal request approved (issuer)'),
    ];
    $form_state->setFormState($message_types);

    foreach ($message_types as $message_type => $title) {
      $form['mail_' . $message_type] = [
        '#type' => 'details',
        '#title' => $title,
      ];

      $form['mail_' . $message_type]['activated'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Activate this email?'),
        '#default_value' => $config->get('mail_' . $message_type)['activated'],
      ];

      $form['mail_' . $message_type]['subject'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#default_value' => $config->get('mail_' . $message_type)['subject'],
      ];

      $form['mail_' . $message_type]['body'] = [
        '#type' => 'text_format',
        '#title' => $this->t('Body'),
        '#default_value' => $config->get('mail_' . $message_type)['body']['value'],
      ];

      if ($this->moduleHandler->moduleExists('token')) {
        $form['mail_' . $message_type]['token_available'] = $this->treeBuilder->buildRenderable([
          'commerce_funds_transaction', 'commerce_funds_balance',
        ]);
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $message_types = $form_state->getStorage();
    foreach ($message_types as $message_type => $title) {
      $this->config('commerce_funds.settings')
        ->set('mail_' . $message_type, $form_state->getValue('mail_' . $message_type))
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

}
