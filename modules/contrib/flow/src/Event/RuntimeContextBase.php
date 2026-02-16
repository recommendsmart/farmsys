<?php

namespace Drupal\flow\Event;

/**
 * Base class for runtime context implementations.
 */
abstract class RuntimeContextBase implements RuntimeContextInterface {

  /**
   * Holds arbitrary context data.
   */
  protected array $contextData = [];

  /**
   * {@inheritdoc}
   */
  public function setContextData(string $key, $value): RuntimeContextInterface {
    $this->contextData[$key] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getContextData(?string $key) {
    if (!isset($key)) {
      return $this->contextData;
    }
    return $this->contextData[$key] ?? NULL;
  }

}
