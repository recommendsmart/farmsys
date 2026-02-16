<?php

namespace Drupal\pagerer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Drupal\Core\Pager\PagerManager;
use Drupal\Core\Pager\PagerParametersInterface;

/**
 * Provides a manager for Pagerer, as an extension of core's PagerManager.
 */
class PagererManager extends PagerManager {

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
   * Construct a PagererManager object.
   *
   * @param \Drupal\Core\Pager\PagerParametersInterface $pager_params
   *   The pager parameters.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(PagerParametersInterface $pager_params, ConfigFactoryInterface $config_factory) {
    parent::__construct($pager_params);
    $this->configFactory = $config_factory;
    $settings = $config_factory->get('pagerer.settings');
    $this->querystringOverride = $settings->get('url_querystring.core_override');
    $this->querystringKey = $this->querystringOverride ? $settings->get('url_querystring.querystring_key') : 'page';
    $this->base = $this->querystringOverride ? $settings->get('url_querystring.index_base') : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function createPager($total, $limit, $element = 0) {
    $pager = new Pagerer($total, $limit, $this->pagerParams->findPage($element));
    $pager->setElement($element);
    $pager->setAdaptiveKeys($this->pagerParams->findAdaptiveKeys($element));
    $this->setPagerer($pager, $element);
    return $pager;
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdatedParameters(array $query, $element, $index) {
    return $this->getPagererUpdatedParameters($this->getPager($element), $query, $index);
  }

  /**
   * Saves a pager to the static cache.
   *
   * @param \Drupal\pagerer\Pagerer $pager
   *   The pager.
   * @param int $element
   *   The pager index.
   */
  protected function setPagerer(Pagerer $pager, int $element = 0) {
    // @todo remove the check below in Drupal 10.
    if (property_exists($this, 'maxPagerElementId')) {
      $this->maxPagerElementId = max($element, $this->maxPagerElementId);
    }
    $this->pagers[$element] = $pager;
    if (method_exists($this, 'updateGlobals')) {
      // @todo remove in Drupal 10.
      $this->updateGlobals();
    }
  }

  /**
   * Gets the URL query parameter array of a pager link.
   *
   * @param Drupal\pagerer\Pagerer $pager
   *   The pager object.
   * @param array $parameters
   *   An associative array of query string parameters to append to the pager
   *   links.
   * @param int $page
   *   The target page.
   * @param array $adaptive_keys
   *   The adaptive keys array, in the format 'L,R,X', where L is the
   *   adaptive lock to left page, R is the adaptive lock to right page,
   *   and X is the adaptive center lock for calculation of neighborhood.
   *
   * @return array
   *   The updated array of query parameters.
   */
  public function getPagererUpdatedParameters(Pagerer $pager, array $parameters, $page, array $adaptive_keys = []): array {
    $max = $this->getMaxPagerElementId();

    // Build the 'page' and 'page_ak' query parameter elements.
    // This is built based on the current page of each pager element (or NULL
    // if the pager is not set), with the exception of the requested page index
    // for the current element.
    $page_el = [];
    $page_ak = [];
    for ($i = 0; $i <= $max; $i++) {
      if (isset($this->pagers[$i])) {
        if ($i === $pager->getElement()) {
          $page_el[$i] = is_string($page) ? $page : $this->pageIndexToUrl($page);
          $page_ak[$i] = $this->adaptiveKeysToUrl($adaptive_keys, $this->pagers[$i]->getLastPage());
        }
        else {
          $page_el[$i] = $this->pageIndexToUrl($this->pagers[$i]->getCurrentPage());
          $page_ak[$i] = $this->adaptiveKeysToUrl($this->pagers[$i]->getAdaptiveKeys(), $this->pagers[$i]->getLastPage());
        }
      }
      else {
        $page_el[$i] = NULL;
        $page_ak[$i] = NULL;
      }
    }

    // Build the 'page' and 'page_ak' fragments, removing unneeded trailing
    // keys.
    while (end($page_el) === NULL) {
      array_pop($page_el);
    }
    while (end($page_ak) === NULL) {
      array_pop($page_ak);
    }

    if (!$this->querystringOverride) {
      // Legacy URL format.
      $parameters[$this->querystringKey] = implode(',', $page_el);
      if (!empty($page_ak)) {
        $parameters['page_ak'] = implode(',', $page_ak);
      }
    }
    else {
      // Pagerer URL format.
      $page = implode(PagererParameters::VALUE_SEPARATOR, $page_el);
      if (!empty($page_ak)) {
        $ak = implode(PagererParameters::VALUE_SEPARATOR, $page_ak);
      }
      $parameters[$this->querystringKey] = $page . (isset($ak) ? PagererParameters::ITEM_SEPARATOR . 'ak' . PagererParameters::ITEM_SEPARATOR . $ak : NULL);
    }

    // Merge the updated pager query parameters, with any parameters coming from
    // the current request. In case of collision, current parameters take
    // precedence over the request ones.
    if ($current_request_query = $this->pagerParams->getQueryParameters()) {
      $parameters = array_merge($current_request_query, $parameters);
    }

    // Explicitly remove the segments if not relevant.
    if ($this->querystringKey !== 'page') {
      unset($parameters['page']);
      unset($parameters['page_ak']);
    }
    if ($this->querystringOverride && empty($page_el) && empty($page_ak)) {
      unset($parameters[$this->querystringKey]);
    }

    return $parameters;
  }

  /**
   * Returns an adaptive keys fragment for use on the URL.
   *
   * @param int[] $ak
   *   The adaptive keys array, in the format 'L,R,X', where L is the adaptive
   *   lock to left page, R is the adaptive lock to right page, and X is the
   *   adaptive center lock for calculation of neighborhood.
   * @param int $last_page
   *   The last page of the queryset.
   *
   * @return string|null
   *   The 0-based or 1-based adaptive keys fragment, depending on the
   *   configuration.
   */
  protected function adaptiveKeysToUrl(array $ak, int $last_page) {
    if (empty($ak)) {
      return NULL;
    }
    if (!isset($ak[2]) && ($ak[0] ?? 0) === 0 && ($ak[1] ?? $last_page) === $last_page) {
      return NULL;
    }
    $tmp[0] = $this->pageIndexToUrl($ak[0] ?? 0);
    $tmp[1] = $this->pageIndexToUrl($ak[1] ?? $last_page);
    if (isset($ak[2])) {
      $tmp[2] = $this->pageIndexToUrl($ak[2]);
    }
    return implode($this->querystringOverride ? '_' : '.', $tmp);
  }

  /**
   * Returns a page index for use on the URL.
   *
   * @param int $page
   *   The target page.
   *
   * @return int
   *   A 0-based or 1-based page index, depending on the configuration.
   */
  protected function pageIndexToUrl(int $page): int {
    return $page + $this->base;
  }

  /**
   * Gets a pager link.
   *
   * @param Drupal\pagerer\Pagerer $pager
   *   The pager object.
   * @param array $parameters
   *   An associative array of query string parameters to append to the pager
   *   links.
   * @param int $page
   *   The target page.
   * @param array $adaptive_keys
   *   (Optional) The adaptive keys array, in the format 'L,R,X', where L is the
   *   adaptive lock to left page, R is the adaptive lock to right page,
   *   and X is the adaptive center lock for calculation of neighborhood.
   * @param bool $set_query
   *   (Optional) Whether the link should contain the query parameters.
   *
   * @return \Drupal\Core\Url
   *   The Url object for the link.
   */
  public function getHref(Pagerer $pager, array $parameters, $page, array $adaptive_keys = [], bool $set_query = TRUE): Url {
    $options = $set_query ? [
      'query' => $this->getPagererUpdatedParameters($pager, $parameters, $page, $adaptive_keys),
    ] : [];
    return Url::fromRoute($pager->getRouteName(), $pager->getRouteParameters(), $options);
  }

}
