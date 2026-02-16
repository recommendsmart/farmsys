<?php

namespace Drupal\commerce_invoice;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_invoice\Entity\InvoiceInterface;
use Drupal\Core\Entity\EntityInterface;

class InvoiceStorage extends CommerceContentEntityStorage {

  /**
   * {@inheritdoc}
   */
  protected function invokeHook($hook, EntityInterface $entity) {
    if ($hook == 'presave') {
      // Invoice::preSave() has completed, now run the storage-level pre-save
      // tasks. These tasks can modify the invoice, so they need to run
      // before the entity/field hooks are invoked.
      $this->doInvoicePresave($entity);
    }

    parent::invokeHook($hook, $entity);
  }

  /**
   * Performs invoice-specific pre-save tasks.
   *
   * This includes:
   * - Recalculating the total price.
   * - Applying the "paid" transition.
   *
   * @param \Drupal\commerce_invoice\Entity\InvoiceInterface $invoice
   *   The invoice.
   */
  protected function doInvoicePresave(InvoiceInterface $invoice) {
    $invoice->recalculateTotalPrice();

    // Apply the "paid" transition when an invoice is paid.
    $original_paid = isset($invoice->original) ? $invoice->original->isPaid() : FALSE;
    if ($invoice->isPaid() && !$original_paid) {
      $invoice->getState()->applyTransitionById('pay');
    }
  }

}
