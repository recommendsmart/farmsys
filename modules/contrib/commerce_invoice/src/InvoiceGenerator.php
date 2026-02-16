<?php

namespace Drupal\commerce_invoice;

use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\profile\Entity\ProfileInterface;

class InvoiceGenerator implements InvoiceGeneratorInterface {

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new InvoiceGenerator object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler) {
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function generate(array $orders, StoreInterface $store, ProfileInterface $profile = NULL, array $values = []) {
    $transaction = $this->connection->startTransaction();
    try {
      return $this->doGenerate($orders, $store, $profile, $values);
    }
    catch (\Exception $exception) {
      $transaction->rollBack();
      watchdog_exception('commerce_invoice', $exception);
      return NULL;
    }
  }

  /**
   * @see \Drupal\commerce_invoice\InvoiceGeneratorInterface::generate()
   */
  protected function doGenerate(array $orders, StoreInterface $store, ProfileInterface $profile = NULL, array $values = []) {
    $invoice_storage = $this->entityTypeManager->getStorage('commerce_invoice');
    $invoice_item_storage = $this->entityTypeManager->getStorage('commerce_invoice_item');
    // Assume the order type from the first passed order, we'll use it
    // to determine the invoice type to create.
    /** @var \Drupal\commerce_order\Entity\OrderInterface $first_order */
    $first_order = reset($orders);

    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = OrderType::load($first_order->bundle());
    $values += [
      'type' => $order_type->getThirdPartySetting('commerce_invoice', 'invoice_type', 'default'),
      'state' => 'draft',
      'store_id' => $store->id(),
    ];

    // If we're generating an invoice for a single order, copy its email.
    if (count($orders) === 1) {
      if (!isset($values['mail']) && !empty($first_order->getEmail())) {
        $values['mail'] = $first_order->getEmail();
      }
      if (!isset($values['uid'])) {
        $values['uid'] = $first_order->getCustomerId();
      }

      if (!isset($values['payment_method'])) {
        if ($first_order->hasField('payment_method') && !$first_order->get('payment_method')->isEmpty()) {
          /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
          $payment_method = $first_order->get('payment_method')->entity;
          if ($payment_method) {
            $values['payment_method'] = $payment_method->label();
          }
        }
        elseif ($first_order->hasField('payment_gateway') && !$first_order->get('payment_gateway')->isEmpty()) {
          $payment_gateway = $first_order->get('payment_gateway')->entity;
          if ($payment_gateway) {
            $values['payment_method'] = $payment_gateway->label();
          }
        }
      }
    }

    /** @var \Drupal\commerce_invoice\Entity\InvoiceInterface $invoice */
    $invoice = $invoice_storage->create($values);

    // If the invoice type is configured to do so, generate the translations
    // for all the available languages.
    if ($this->moduleHandler->moduleExists('language')) {
      $config = ContentLanguageSettings::loadByEntityTypeBundle('commerce_invoice', $invoice->bundle());
      if ($config && $config->getThirdPartySetting('commerce_invoice', 'generate_translations', FALSE)) {
        $languages = $this->languageManager->getLanguages();
        foreach ($languages as $langcode => $language) {
          if ($invoice->hasTranslation($langcode)) {
            continue;
          }
          // Currently, only the data field is translatable on invoices, we
          // store the invoice type data there and make sure the translated data
          // is stored inside Invoice::presave().
          $invoice->addTranslation($langcode);
        }
      }
    }

    if ($profile) {
      $billing_profile = $profile->createDuplicate();
      $billing_profile->save();
      $invoice->setBillingProfile($billing_profile);
    }
    // Get the default invoice language so we can set it on invoice items.
    $default_langcode = $invoice->language()->getId();

    $total_paid = NULL;
    /** @var \Drupal\commerce_order\Entity\OrderInterface[] $orders */
    foreach ($orders as $order) {
      foreach ($order->getAdjustments() as $adjustment) {
        $invoice->addAdjustment($adjustment);
      }
      foreach ($order->getItems() as $order_item) {
        /** @var \Drupal\commerce_order\Entity\OrderItemTypeInterface $order_item_type */
        $order_item_type = OrderItemType::load($order_item->bundle());
        $invoice_item_type = $order_item_type->getPurchasableEntityTypeId() ?: 'default';
        /** @var \Drupal\commerce_invoice\Entity\InvoiceItemInterface $invoice_item */
        $invoice_item = $invoice_item_storage->create([
          'langcode' => $default_langcode,
          'type' => $invoice_item_type,
        ]);

        $invoice_item->populateFromOrderItem($order_item);
        // If the invoice is translated, we need to generate translations in
        // all languages for each invoice item.
        foreach ($invoice->getTranslationLanguages(FALSE) as $langcode => $language) {
          $translated_invoice_item = $invoice_item->addTranslation($langcode);
          // We're calling InvoiceItem::populateFromOrderItem() for each
          // translation since that logic is responsible for pulling the
          // translated variation title, if available.
          $translated_invoice_item->populateFromOrderItem($order_item);
        }

        $invoice_item->save();
        $invoice->addItem($invoice_item);
      }
      $total_paid = $total_paid ? $total_paid->add($order->getTotalPaid()) : $order->getTotalPaid();
    }
    if ($total_paid) {
      $invoice->setTotalPaid($total_paid);
    }
    $invoice->setOrders($orders);

    if ($invoice->getState()->getId() === 'draft') {
      $invoice->getState()->applyTransitionById('confirm');
    }

    $invoice->save();
    return $invoice;
  }

}
