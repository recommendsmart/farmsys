<?php

namespace Drupal\commerce_invoice\Mail;

use Drupal\commerce\MailHandlerInterface;
use Drupal\commerce_invoice\Entity\InvoiceInterface;
use Drupal\commerce_invoice\InvoiceFileManagerInterface;
use Drupal\commerce_invoice\InvoiceTotalSummaryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class InvoiceConfirmationMail implements InvoiceConfirmationMailInterface {

  use StringTranslationTrait;

  /**
   * The mail handler.
   *
   * @var \Drupal\commerce\MailHandlerInterface
   */
  protected $mailHandler;

  /**
   * The invoice total summary.
   *
   * @var \Drupal\commerce_invoice\InvoiceTotalSummaryInterface
   */
  protected $invoiceTotalSummary;

  /**
   * The profile view builder.
   *
   * @var \Drupal\profile\ProfileViewBuilder
   */
  protected $profileViewBuilder;

  /**
   * The invoice file manager.
   *
   * @var \Drupal\commerce_invoice\InvoiceFileManagerInterface
   */
  protected $invoiceFileManager;

  /**
   * Constructs a new InvoiceConfirmationMail object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce\MailHandlerInterface $mail_handler
   *   The mail handler.
   * @param \Drupal\commerce_invoice\InvoiceTotalSummaryInterface $invoice_total_summary
   *   The invoice total summary.
   * @param \Drupal\commerce_invoice\InvoiceFileManagerInterface $invoice_file_manager
   *   The invoice file manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MailHandlerInterface $mail_handler, InvoiceTotalSummaryInterface $invoice_total_summary, InvoiceFileManagerInterface $invoice_file_manager) {
    $this->mailHandler = $mail_handler;
    $this->invoiceTotalSummary = $invoice_total_summary;
    $this->profileViewBuilder = $entity_type_manager->getViewBuilder('profile');
    $this->invoiceFileManager = $invoice_file_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function send(InvoiceInterface $invoice, $to = NULL, $bcc = NULL) {
    $to = isset($to) ? $to : $invoice->getEmail();
    if (!$to) {
      // The email should not be empty.
      return FALSE;
    }

    $subject = $this->t('Invoice #@number', ['@number' => $invoice->getInvoiceNumber()]);
    $body = [
      '#theme' => 'commerce_invoice_confirmation',
      '#invoice_entity' => $invoice,
      '#totals' => $this->invoiceTotalSummary->buildTotals($invoice),
    ];
    if ($billing_profile = $invoice->getBillingProfile()) {
      $body['#billing_information'] = $this->profileViewBuilder->view($billing_profile);
    }

    $params = [
      'id' => 'invoice_confirmation',
      'from' => $invoice->getStore()->getEmail(),
      'bcc' => $bcc,
      'invoice' => $invoice,
    ];
    $customer = $invoice->getCustomer();
    if ($customer->isAuthenticated()) {
      $params['langcode'] = $customer->getPreferredLangcode();
    }
    $file = $this->invoiceFileManager->getInvoiceFile($invoice);
    $attachment = [
      'filepath' => $file->getFileUri(),
      'filename' => $file->getFilename(),
      'filemime' => $file->getMimeType(),
    ];
    $params['attachments'][] = $attachment;

    return $this->mailHandler->sendMail($to, $subject, $body, $params);
  }

}
