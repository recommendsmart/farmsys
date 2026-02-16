<?php

namespace Drupal\basket\Plugin\views\cache;

use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\Core\Cache\Cache;

/**
 * Simple caching of query results for Views displays.
 *
 * @ingroup views_cache_plugins
 *
 * @ViewsCache(
 *   id = "basket_cache",
 *   title = @Translation("Basket goods cache"),
 * )
 */
class BasketCache extends CachePluginBase {

  /**
   * Set getCurrencyTag.
   *
   * @var string
   */
  protected static $getCurrencyTag;

  /**
   * Set setCurKey.
   *
   * @var bool
   */
  protected static $setCurKey;

  /**
   * Gets an array of cache tags for the current view.
   *
   * @return string[]
   *   An array of cache tags based on the current view.
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();
    $tags[] = self::getCurrencyCacheTag();
    return $tags;
  }

  /**
   * Calculates and sets a cache ID used for the result cache.
   *
   * @return string
   *   The generated cache ID.
   */
  public function generateResultsKey() {
    $this->resultsKey = parent::generateResultsKey();
    if (!self::$setCurKey) {
      $this->resultsKey = self::getCurrencyCacheTag() . ':' . $this->resultsKey;
      self::$setCurKey = TRUE;
    }
    return $this->resultsKey;
  }

  /**
   * {@inheritdoc}
   */
  private static function getCurrencyCacheTag() {
    if (!self::$getCurrencyTag) {
      self::$getCurrencyTag = 'basket_currency.' . \Drupal::service('Basket')->Currency()->getCurrent();
    }
    return self::$getCurrencyTag;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\cache\CachePluginBase::cacheGet().
   *
   * Replace the cache get logic so it does not return a cache item at all.
   */
  public function cacheGet($type) {
    $cutoff = $this->cacheExpire($type);
    switch ($type) {
      case 'query':
        // Not supported currently, but this is certainly where we'd put it.
        return FALSE;

      case 'results':
        // Values to set: $view->result, $view->total_rows, $view->execute_time,
        // $view->current_page.
        if ($cache = \Drupal::cache($this->resultsBin)->get($this->generateResultsKey())) {
          if (!$cutoff || $cache->created > $cutoff) {
            $this->view->result = $cache->data['result'];
            // Load entities for each result.
            $this->view->query->loadEntities($this->view->result);
            $this->view->total_rows = $cache->data['total_rows'];
            $this->view->setCurrentPage($cache->data['current_page'], TRUE);
            $this->view->execute_time = 0;
            return TRUE;
          }
        }
        break;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * Replace the cache set logic so it does not set a cache item at all.
   */
  public function cacheSet($type) {
    switch ($type) {
      case 'query':
        // Not supported currently, but this is certainly where we'd put it.
        break;

      case 'results':
        $data = [
          'result' => $this->prepareViewResult($this->view->result),
          'total_rows' => isset($this->view->total_rows) ? $this->view->total_rows : 0,
          'current_page' => $this->view->getCurrentPage(),
        ];
        $expire = ($this->cacheSetMaxAge($type) === Cache::PERMANENT) ? Cache::PERMANENT : (int) $this->view->getRequest()->server->get('REQUEST_TIME') + $this->cacheSetMaxAge($type);
        \Drupal::cache($this->resultsBin)->set($this->generateResultsKey(), $data, $expire, $this->getCacheTags());
        break;
    }
  }

}
