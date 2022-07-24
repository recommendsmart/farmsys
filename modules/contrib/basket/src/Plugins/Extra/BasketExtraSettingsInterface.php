<?php

namespace Drupal\basket\Plugins\Extra;

/**
 * Provides an interface for all Basket Extra plugins.
 */
interface BasketExtraSettingsInterface {

  /**
   * Gets extra field settings form.
   *
   * @param string $field_name
   *   The extra field machine name.
   *
   * @return array
   *   Array with form fields or empty array.
   */
  public function getSettingsForm();

  /**
   * Gets extra field settings summary.
   *
   * @return string
   */
  public function getSettingsSummary($settings);
}
