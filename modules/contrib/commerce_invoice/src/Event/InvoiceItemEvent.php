<?php

namespace Drupal\commerce_invoice\Event;

use Drupal\commerce_invoice\Entity\InvoiceItemInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the invoice item event.
 */
class InvoiceItemEvent extends Event {

  /**
   * The invoice item.
   *
   * @var \Drupal\commerce_invoice\Entity\InvoiceItemInterface
   */
  protected $invoiceItem;

  /**
   * Constructs a new InvoiceItemEvent.
   *
   * @param \Drupal\commerce_invoice\Entity\InvoiceItemInterface $invoice_item
   *   The invoice item.
   */
  public function __construct(InvoiceItemInterface $invoice_item) {
    $this->invoiceItem = $invoice_item;
  }

  /**
   * Gets the invoice item.
   *
   * @return \Drupal\commerce_invoice\Entity\InvoiceItemInterface
   *   The invoice item.
   */
  public function getInvoiceItem() {
    return $this->invoiceItem;
  }

}
