<?php

namespace Drupal\pagerer;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Pager\PagerParameters;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides extended pager information contained within the current request.
 */
class PagererParameters extends PagerParameters {

  /**
   * The character to use to separate items in the querystring parameter.
   */
  const ITEM_SEPARATOR = '-';

  /**
   * The character to use to separate values in an item.
   */
  const VALUE_SEPARATOR = '.';

  /**
   * Whether the URL querystring pager key is overridden.
   *
   * @var bool
   */
  protected $querystringOverride;

  /**
   * The URL querystring pager key ('page' by default, or the overriding one).
   *
   * @var string
   */
  protected $querystringKey;

  /**
   * The numbering base of the pages in the URL (zero or one).
   *
   * @var int
   */
  protected $base;

  /**
   * The 'pagerer items' parsed from the request's querystring.
   *
   * The items are stored in an array indexed by the hash of the request. Each
   * request has two items:
   * - 'page' - the array of page indexes, zero based
   * - 'page_ak' - the array of adaptive keys arrays, zero based.
   *
   * @var array
   */
  protected $pagererItems;

  /**
   * Construct a PagererParameters object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $stack
   *   The current HTTP request stack.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(RequestStack $stack, ConfigFactoryInterface $config_factory) {
    parent::__construct($stack);
    $this->configFactory = $config_factory;
    $settings = $config_factory->get('pagerer.settings');
    $this->querystringOverride = $settings->get('url_querystring.core_override');
    $this->querystringKey = $this->querystringOverride ? $settings->get('url_querystring.querystring_key') : 'page';
    $this->base = $this->querystringOverride ? $settings->get('url_querystring.index_base') : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryParameters() {
    // Differs from core in the sense that also 'page_ak' and the overriding
    // querystring key (if configured) are removed.
    if ($request = $this->requestStack->getCurrentRequest()) {
      $filter = ['page', 'page_ak'];
      if ($this->querystringOverride) {
        $filter[] = $this->querystringKey;
      }
      return UrlHelper::filterQueryParameters($request->query->all(), $filter);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPagerQuery() {
    // Differs from core in the sense that the array of pagers is taken from the
    // pre-parsed querystring stored in ::$pagererItems and not from the
    // querystring every time.
    return $this->getPagererItem('page') ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPagerParameter() {
    // Differs from core in the sense that the string returned is the one
    // identified by the ::querystringKey if the URL is overridden.
    if ($request = $this->requestStack->getCurrentRequest()) {
      if ($this->querystringOverride && $request->query->has($this->querystringKey)) {
        return $request->query->get($this->querystringKey);
      }
      return $request->query->get('page', '');
    }
    return '';
  }

  /**
   * Returns a Pagerer 'pager item'.
   *
   * Parse the request and store its values in ::$pagererItems for later faster
   * access.
   *
   * @param string $item_id
   *   The key in the ::$pagererItems array for the item to be returned.
   *
   * @return mixed|null
   *   The 'pager item', or NULL if missing.
   */
  protected function getPagererItem(string $item_id) {
    // We calculate an hash of the current request to cater for the case when
    // the querystring changes in different requests on the stack.
    $request = $this->requestStack->getCurrentRequest();
    $hash = hash('md5', (string) $request);

    if (!isset($this->pagererItems[$hash])) {
      if ($this->querystringOverride && $request->query->has($this->querystringKey)) {
        // Overriden URL querystring exists.
        $this->parsePagerer($hash, $request->query->get($this->querystringKey));
      }
      else {
        // No overriden URL querystring exists, try 'page' + 'page_ak'.
        $this->parseLegacy($hash, $request->query->get('page', ''), $request->query->get('page_ak', ''));
      }
    }

    return $this->pagererItems[$hash][$item_id] ?? NULL;
  }

  /**
   * Parses a Pagerer pager querystring.
   *
   * @param string $hash
   *   The hash of the current request.
   * @param string $querystring
   *   The value of the querystring parameter identified by ::querystringKey.
   */
  protected function parsePagerer(string $hash, string $querystring) {
    $items = explode(static::ITEM_SEPARATOR, $querystring);
    // Current pages.
    $tmp = explode(static::VALUE_SEPARATOR, $items[0]);
    foreach ($tmp as $pager_id => $page) {
      $this->pagererItems[$hash]['page'][$pager_id] = $this->pageUrlValueToIndex((int) $page);
    }
    // Current adaptive keys.
    if (isset($items[1]) && $items[1] === 'ak' && isset($items[2])) {
      $tmp = explode(static::VALUE_SEPARATOR, $items[2]);
      foreach ($tmp as $pager_id => $ak) {
        $tmp_0 = explode('_', $ak);
        $this->pagererItems[$hash]['ak'][$pager_id][0] = !empty($tmp_0[0]) ? $this->pageUrlValueToIndex((int) $tmp_0[0]) : NULL;
        $this->pagererItems[$hash]['ak'][$pager_id][1] = isset($tmp_0[1]) ? $this->pageUrlValueToIndex((int) $tmp_0[1]) : NULL;
        if (isset($tmp_0[2])) {
          $this->pagererItems[$hash]['ak'][$pager_id][2] = $this->pageUrlValueToIndex((int) $tmp_0[2]);
        }
      }
    }
  }

  /**
   * Parses a legacy pager querystring.
   *
   * @param string $hash
   *   The hash of the current request.
   * @param string $page
   *   The value of the 'page' querystring parameter.
   * @param string $page_ak
   *   The value of the 'page_ak' querystring parameter.
   */
  protected function parseLegacy(string $hash, string $page, string $page_ak) {
    // Current pages.
    if (!empty($page)) {
      $tmp = explode(',', $page);
      foreach ($tmp as $pager_id => $page) {
        $this->pagererItems[$hash]['page'][$pager_id] = (int) $page;
      }
    }
    // Current adaptive keys.
    if (!empty($page_ak)) {
      $tmp = explode(',', $page_ak);
      foreach ($tmp as $pager_id => $ak) {
        $tmp_0 = explode('.', $ak);
        $this->pagererItems[$hash]['ak'][$pager_id][0] = !empty($tmp_0[0]) ? $tmp_0[0] : NULL;
        $this->pagererItems[$hash]['ak'][$pager_id][1] = isset($tmp_0[1]) ? (int) $tmp_0[1] : NULL;
        if (isset($tmp_0[2])) {
          $this->pagererItems[$hash]['ak'][$pager_id][2] = (int) $tmp_0[2];
        }
      }
    }
  }

  /**
   * Returns a zero-based page index from the URL value.
   *
   * @param int $value
   *   The value as exposed in the URL.
   *
   * @return int
   *   The zero-based page index.
   */
  protected function pageUrlValueToIndex(int $value): int {
    return max(0, $value - $this->base);
  }

  /**
   * Returns the current adaptive keys for a pager element.
   *
   * @param int $pager_id
   *   (optional) An integer to distinguish between multiple pagers on one page.
   *
   * @return array
   *   The adaptive keys array, in the format 'L,R,X', where L is the adaptive
   *   lock to left page, R is the adaptive lock to right page, and X is the
   *   adaptive center lock for calculation of neighborhood.
   */
  public function findAdaptiveKeys(int $pager_id = 0): array {
    return $this->getPagererItem('ak')[$pager_id] ?? [];
  }

}
