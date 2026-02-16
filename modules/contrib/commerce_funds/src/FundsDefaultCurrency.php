<?php

namespace Drupal\commerce_funds;

use Drupal\commerce_store\Entity\Store;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Print default currency class.
 */
final class FundsDefaultCurrency {

  use \Drupal\Core\StringTranslation\StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Defines default currency code.
   *
   * @var string
   */
  protected $defaultCurrencyCode;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_store\Entity\Store $store
   *   The store.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Store $store) {
    $this->entityTypeManager = $entity_type_manager;
    $this->defaultCurrencyCode = $store->getDefaultCurrencyCode();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, Store $store) {
    return new static(
      $container->get('entity_type.manager'),
      $store
    );
  }

  /**
   * Display default currency or "all currencies".
   *
   * @return string
   *   Default currency code or "all currencies".
   */
  public function printConfigureFeesCurrency() {
    $default_currency = $this->defaultCurrencyCode;

    if (!$default_currency) {
      return $this->t('No currency set');
    }

    $currencies = $this->entityTypeManager->getStorage('commerce_currency')->loadMultiple();
    $currency_qty = count($currencies);

    if ($currency_qty > 1) {
      return $this->t('All currencies');
    }
    elseif ($currency_qty == 1) {
      return $default_currency;
    }
    else {
      throw new \InvalidArgumentException('FundsDefaultCurrency::printConfigureFeesCurrency() called with a malformed store object.');
    }
  }

  /**
   * Display default currency or "Selected currency".
   *
   * @return string
   *   Default currency code or "Selected currency".
   */
  public function printTransactionCurrency() {
    $currencies = $this->entityTypeManager->getStorage('commerce_currency')->loadMultiple();
    $currency_qty = count($currencies);

    if ($currency_qty > 1) {
      return $this->t('unit(s) of selected currency');
    }
    elseif ($currency_qty == 1) {
      return $this->defaultCurrencyCode;
    }
    else {
      throw new \InvalidArgumentException('FundsDefaultCurrency::printTransactionCurrency() called with a malformed store object.');
    }
  }

}
