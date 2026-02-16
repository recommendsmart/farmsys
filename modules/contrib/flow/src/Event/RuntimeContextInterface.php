<?php

namespace Drupal\flow\Event;

/**
 * Interface for runtime context passed along Flow-related events.
 */
interface RuntimeContextInterface {

  /**
   * Set arbitrary context data.
   *
   * @param string $key
   *   The key that identifies the data to be retrieved via ::getContextData().
   *
   * @return $this
   */
  public function setContextData(string $key, $value): RuntimeContextInterface;

  /**
   * Get arbitrary context data.
   *
   * @return mixed
   *   Returns an array when no $key was specified, and returns NULL if no
   *   data exists for a specified $key. When the data exists, then the value
   *   will be returned as it was added via ::setContextData(). When the data
   *   is not an object, a copy (not a reference) of the value will be returned.
   */
  public function getContextData(?string $key);

}
