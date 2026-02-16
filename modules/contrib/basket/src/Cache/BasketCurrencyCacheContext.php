<?php

namespace Drupal\basket\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\Cache;

/**
 * Basket cash context.
 */
class BasketCurrencyCacheContext implements CacheContextInterface {

  const CACHE_TAG = 'basket:currency';

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Basket currency');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return \Drupal::service('Basket')->Currency()->getCurrent();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTag() {
    return $this::CACHE_TAG;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCacheTag() {
    Cache::invalidateTags([$this::CACHE_TAG]);
  }

}
