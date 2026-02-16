<?php

namespace Drupal\commerce_invoice\Form;

use Drupal\commerce_invoice\InvoiceGeneratorInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InvoiceGenerateForm extends ConfirmFormBase {

  /**
   * The invoice generator.
   *
   * @var \Drupal\commerce_invoice\InvoiceGeneratorInterface
   */
  protected $invoiceGenerator;

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Constructs a new InvoiceGenerateForm object.
   *
   * @param \Drupal\commerce_invoice\InvoiceGeneratorInterface $invoice_generator
   *   The invoice generator.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(InvoiceGeneratorInterface $invoice_generator, MessengerInterface $messenger) {
    $this->invoiceGenerator = $invoice_generator;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_invoice.invoice_generator'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_invoice_generate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Generate');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to generate an invoice for the order %title?', [
      '%title' => $this->order->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->order->toUrl('invoices');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $commerce_order = NULL) {
    $this->order = $commerce_order;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $order = $this->order;
    $invoice = $this->invoiceGenerator->generate([$order], $order->getStore(), $order->getBillingProfile(), ['uid' => $order->getCustomerId()]);

    if ($invoice) {
      $this->messenger->addMessage($this->t('Invoice %label successfully generated.', ['%label' => $invoice->label()]));
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
