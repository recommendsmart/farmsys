<?php

declare(strict_types=1);

namespace Drupal\commerce_transaction\Hook;

use Drupal\commerce_transaction\Updater\SupportsTransactionUpdatingInterface;
use Drupal\commerce_transaction\Updater\UpdateManagerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Holds methods implementing hooks related to cron.
 */
class Cron {

  /**
   * The payment type plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $paymentTypeManager;

  /**
   * The transaction update manager.
   *
   * @var \Drupal\commerce_transaction\Updater\UpdateManagerInterface
   */
  protected $transactionUpdater;

  /**
   * Constructs a new Cron object.
   *
   * @param \Drupal\commerce_transaction\Updater\UpdateManagerInterface $transaction_updater
   *   The transaction update manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $payment_type_manager
   *   The payment type plugin manager.
   */
  public function __construct(
    UpdateManagerInterface $transaction_updater,
    PluginManagerInterface $payment_type_manager
  ) {
    $this->transactionUpdater = $transaction_updater;
    $this->paymentTypeManager = $payment_type_manager;
  }

  /**
   * Updates pending transactions for supporting payment types.
   */
  public function run() {
    $definitions = $this->paymentTypeManager->getDefinitions();
    foreach ($definitions as $definition) {
      $plugin = $this->paymentTypeManager->createInstance($definition['id']);
      if (!$plugin instanceof SupportsTransactionUpdatingInterface) {
        continue;
      }

      $this->transactionUpdater->updateForPaymentType(
        $plugin,
        ['access_check' => FALSE]
      );

    }
  }

}
