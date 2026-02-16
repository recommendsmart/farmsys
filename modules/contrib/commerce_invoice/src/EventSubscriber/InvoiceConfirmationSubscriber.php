<?php

namespace Drupal\commerce_invoice\EventSubscriber;

use Drupal\commerce_invoice\Mail\InvoiceConfirmationMailInterface;
use Drupal\Core\DestructableInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sends a confirmation email when an invoice is generated.
 */
class InvoiceConfirmationSubscriber implements EventSubscriberInterface, DestructableInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The invoice confirmation mail.
   *
   * @var \Drupal\commerce_invoice\Mail\InvoiceConfirmationMailInterface
   */
  protected $invoiceConfirmationMail;

  /**
   * The invoice IDs for which we should send invoice confirmation emails.
   *
   * @var int[]
   */
  protected $invoicesList = [];

  /**
   * Constructs a new InvoiceConfirmationSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_invoice\Mail\InvoiceConfirmationMailInterface $invoice_confirmation_mail
   *   The mail handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, InvoiceConfirmationMailInterface $invoice_confirmation_mail) {
    $this->entityTypeManager = $entity_type_manager;
    $this->invoiceConfirmationMail = $invoice_confirmation_mail;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'commerce_invoice.confirm.post_transition' => ['sendInvoiceConfirmation', -100],
    ];
  }

  /**
   * Sends an invoice confirmation email.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function sendInvoiceConfirmation(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_invoice\Entity\InvoiceInterface $invoice */
    $invoice = $event->getEntity();

    $invoice_type_storage = $this->entityTypeManager->getStorage('commerce_invoice_type');
    /** @var \Drupal\commerce_invoice\Entity\InvoiceTypeInterface $invoice_type */
    $invoice_type = $invoice_type_storage->load($invoice->bundle());
    // Don't send the invoice confirmation email right away because doing so
    // triggers an invoice save after generating the invoice file.
    if ($invoice_type->shouldSendConfirmation()) {
      $this->invoicesList[$invoice->id()] = $invoice->id();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {
    if (empty($this->invoicesList)) {
      return;
    }
    $invoice_storage = $this->entityTypeManager->getStorage('commerce_invoice');
    $invoice_type_storage = $this->entityTypeManager->getStorage('commerce_invoice_type');
    /** @var \Drupal\commerce_invoice\Entity\InvoiceInterface[] $invoices */
    $invoices = $invoice_storage->loadMultiple($this->invoicesList);
    foreach ($invoices as $invoice) {
      // If the invoice is already paid, skip sending the email.
      if ($invoice->isPaid()) {
        continue;
      }
      /** @var \Drupal\commerce_invoice\Entity\InvoiceTypeInterface $invoice_type */
      $invoice_type = $invoice_type_storage->load($invoice->bundle());
      $this->invoiceConfirmationMail->send($invoice, $invoice->getEmail(), $invoice_type->getConfirmationBcc());
    }
  }

}
