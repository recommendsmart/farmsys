<?php

namespace Drupal\basket;

/**
 * {@inheritdoc}
 */
class BasketCurrency {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set tree.
   *
   * @var array
   */
  protected $tree;

  /**
   * Set currency.
   *
   * @var object
   */
  protected $currency;

  /**
   * Set currencyIso.
   *
   * @var object
   */
  protected $currencyIso;

  /**
   * Set getOptions.
   *
   * @var array
   */
  protected $getOptions;

  /**
   * Set getOptions.
   *
   * @var array
   */
  protected $getCurrent;

  /**
   * Set getPayCurrency.
   *
   * @var array
   */
  protected $getPayCurrency;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function tree() {
    if (!isset($this->tree)) {
      $this->tree = [];
      $results = \Drupal::database()->select('basket_currency', 'c')
        ->fields('c')
        ->orderBy('c.weight', 'ASC')
        ->orderBy('c.name', 'ASC')
        ->execute()->fetchAll();
      if (!empty($results)) {
        foreach ($results as $result) {
          $this->tree[$result->id] = $result;
        }
      }
    }
    return $this->tree;
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    if (!isset($this->currency[$id])) {
      $this->currency[$id] = \Drupal::database()->select('basket_currency', 'c')
        ->fields('c')
        ->condition('c.id', $id)
        ->execute()->fetchObject();
    }
    return $this->currency[$id];
  }

  /**
   * {@inheritdoc}
   */
  public function loadByIso($iso) {
    if (!isset($this->currencyIso[$iso])) {
      $this->currencyIso[$iso] = \Drupal::database()->select('basket_currency', 'c')
        ->fields('c')
        ->condition('c.iso', $iso)
        ->execute()->fetchObject();
    }
    return $this->currencyIso[$iso];
  }

  /**
   * {@inheritdoc}
   */
  public function delete($id) {
    \Drupal::database()->delete('basket_currency')
      ->condition('id', $id)
      ->isNull('locked')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function updateRate($currency, $rate) {
    if (!empty($currency->id)) {
      \Drupal::database()->update('basket_currency')
        ->fields([
          'rate'      => $rate,
        ])
        ->condition('id', $currency->id)
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setDefault($id) {
    \Drupal::database()->update('basket_currency')
      ->fields([
        'default'       => NULL,
      ])
      ->execute();
    \Drupal::database()->update('basket_currency')
      ->fields([
        'default'       => 1,
      ])
      ->condition('id', $id)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions($viewPrefix = FALSE) {
    if (!isset($this->getOptions[(boolean) $viewPrefix])) {
      $this->getOptions[(boolean) $viewPrefix] = [];
      foreach ($this->tree() as $currency) {
        if ($viewPrefix && !empty($currency->name_prefix)) {
          $currency->name = $currency->name_prefix . ' ' . $currency->name;
        }
        $this->getOptions[(boolean) $viewPrefix][$currency->id] = $this->basket->Translate()->trans($currency->name);
      }
    }
    return $this->getOptions[(boolean) $viewPrefix];
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrent($id) {
    $_SESSION['basket_currency'] = $id;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrent($loadDef = FALSE) {
    $loadDef = (boolean) $loadDef;
    if (!isset($this->getCurrent[$loadDef])) {
      if (!empty($loadDef)) {
        $this->getCurrent[$loadDef] = \Drupal::database()->select('basket_currency', 'c')
          ->fields('c', ['id'])
          ->condition('c.default', 1)
          ->execute()->fetchField();
      }
      else {
        $options = $this->getOptions();
        if (empty($loadDef) && !empty($_SESSION['basket_currency']) && !empty($options[$_SESSION['basket_currency']])) {
          $this->getCurrent[$loadDef] = $_SESSION['basket_currency'];
        }
        else {
          $this->getCurrent[$loadDef] = $this->getCurrent(TRUE);
        }
      }
      // Alter.
      if (empty($loadDef)) {
        \Drupal::moduleHandler()->alter('basket_current_currency', $this->getCurrent[$loadDef]);
      }
      // ---
    }
    return $this->getCurrent[$loadDef];
  }

  /**
   * {@inheritdoc}
   */
  public function getPayCurrency($getData = FALSE) {
    if (!$this->getPayCurrency) {
      $this->getPayCurrency = \Drupal::service('Basket')->getSettings('currency_pay_order', 'cid');
      if ($this->getPayCurrency == 'all') {
        $this->getPayCurrency = $getData ? $this->getPayCurrency : $this->getCurrent();
      }
      elseif (!empty($this->getPayCurrency) && empty($this->tree()[$this->getPayCurrency])) {
        $this->getPayCurrency = NULL;
      }
      if (empty($this->getPayCurrency)) {
        $this->getPayCurrency = $this->getCurrent(TRUE);
      }
    }
    return $this->getPayCurrency;
  }

  /**
   * {@inheritdoc}
   */
  public function priceConvert(&$price, &$currency, $loadDef = FALSE, $getCurrent = NULL) {
    if (empty($getCurrent)) {
      $getCurrent = $this->getCurrent($loadDef);
    }
    $currency_ = $this->load($currency);
    $getCurrent = $this->load($getCurrent);
    if (!empty($currency_->rate) && !empty($getCurrent->rate)) {
      $price = $price * ($currency_->rate / $getCurrent->rate);
      $currency = $getCurrent->id;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache() {
    \Drupal::service('cache_context.basket_currency')->clearCacheTag();
    \Drupal::moduleHandler()->invokeAll('currency_clear_cache', []);
  }

}
