<?php

namespace Drupal\pagerer;

use Drupal\Core\Pager\Pager;

/**
 * Pagerer pager value object.
 */
class Pagerer extends Pager {

  /**
   * The pager element.
   *
   * This is the index used by query extenders to identify the query to be
   * paged, and reflected in the 'page=x,y,z' query parameter of the HTTP
   * request.
   *
   * @var int
   */
  protected $element;

  /**
   * The route name.
   *
   * @var string
   */
  protected $routeName;

  /**
   * The route parameters.
   *
   * @var string[]
   */
  protected $routeParameters = [];

  /**
   * The pager adaptive keys.
   *
   * @var string
   */
  protected $adaptiveKeys;

  /**
   * Gets the route name for this pager.
   *
   * @return string
   *   The route name.
   */
  public function getRouteName() {
    return $this->routeName;
  }

  /**
   * Sets the route name for this pager.
   *
   * @param string $route_name
   *   The route name.
   *
   * @return self
   *   Self.
   */
  public function setRouteName($route_name): self {
    $this->routeName = $route_name;
    return $this;
  }

  /**
   * Gets the route parameters for this pager.
   *
   * @return string[]
   *   The route parameters.
   */
  public function getRouteParameters(): array {
    return $this->routeParameters;
  }

  /**
   * Sets the route parameters for this pager.
   *
   * @param string[] $route_parameters
   *   The route parameters.
   *
   * @return self
   *   Self.
   */
  public function setRouteParameters(array $route_parameters): self {
    $this->routeParameters = $route_parameters;
    return $this;
  }

  /**
   * Gets the pager element.
   *
   * @return int
   *   The pager element.
   */
  public function getElement(): int {
    return $this->element;
  }

  /**
   * Sets the pager element.
   *
   * @param int $element
   *   The pager element.
   *
   * @return self
   *   Self.
   */
  public function setElement(int $element): self {
    $this->element = $element;
    return $this;
  }

  /**
   * Gets last page in the pager (zero-index).
   *
   * @return int
   *   The index of the last page in the pager.
   */
  public function getLastPage(): int {
    return $this->totalPages - 1;
  }

  /**
   * Gets the adaptive keys of this pager.
   *
   * Used by the Adaptive pager style.
   *
   * @return array
   *   The adaptive keys array, in the format 'L,R,X', where L is the
   *   adaptive lock to left page, R is the adaptive lock to right page,
   *   and X is the adaptive center lock for calculation of neighborhood.
   */
  public function getAdaptiveKeys(): array {
    return $this->adaptiveKeys;
  }

  /**
   * Sets the adaptive keys of this pager.
   *
   * Used by the Adaptive pager style.
   *
   * @param array $adaptive_keys
   *   The adaptive keys array, in the format 'L,R,X', where L is the adaptive
   *   lock to left page, R is the adaptive lock to right page, and X is the
   *   adaptive center lock for calculation of neighborhood.
   *
   * @return self
   *   Self.
   */
  public function setAdaptiveKeys(array $adaptive_keys): self {
    $this->adaptiveKeys = $adaptive_keys;
    return $this;
  }

}
