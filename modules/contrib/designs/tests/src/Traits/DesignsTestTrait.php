<?php

namespace Drupal\Tests\designs\Traits;

/**
 * Provide simplified testing of front end user design.
 */
trait DesignsTestTrait {

  /**
   * Perform the configuration of the design settings.
   *
   * @param string $parents
   *   The parents of the form element representing the design.
   * @param array $settings
   *   The settings.
   */
  protected function drupalSetupDesignSettings($parents, array $settings) {
    if (!is_array($settings)) {
      return;
    }

    $button = trim(preg_replace('/[\[\]]+/', '-', $parents), '-');
    foreach ($settings as $setting_id => $setting) {
      // Get the setting values and set the values outside of the plugin
      // configuration.
      $values = [];
      foreach ($setting as $key => $value) {
        if ($key !== 'config') {
          $values["{$parents}[settings][{$setting_id}][{$key}]"] = $value;
        }
      }

      // Submit the form for the settings values, updating the plugin
      // form as well.
      $this->submitForm($values, "{$button}-settings-{$setting_id}-submit");

      // Update the settings plugin form.
      if (isset($setting['config'])) {
        $values = [];
        foreach ($setting['config'] as $key => $value) {
          $values["{$parents}[settings][{$setting_id}][config][{$key}]"] = $value;
        }
        $this->submitForm($values, "{$button}-settings-{$setting_id}-submit");
      }
    }
  }

  /**
   * Perform the configuration of the design custom content.
   *
   * @param string $parents
   *   The parents of the form element representing the design.
   * @param array $content
   *   The contents.
   */
  protected function drupalSetupDesignContent($parents, array $content) {
    if (!is_array($content)) {
      return;
    }

    $button = trim(preg_replace('/[\[\]]+/', '-', $parents), '-');
    foreach ($content as $custom_id => $custom) {
      // Perform the creation of the custom content first.
      $this->submitForm([
        "{$button}-content-addition-label" => $custom['config']['label'] ?? $custom_id,
        "{$button}-content-addition-machine" => $custom_id,
      ], "{$button}-content-addition-create");

      // Perform setup of the content plugin.
      if (isset($custom['plugin'])) {
        $this->submitForm([
          "{$parents}[content][{$custom_id}][plugin]" => $custom['plugin'],
        ], "{$button}-content-{$custom_id}-submit");
      }
      // Perform setup of the content plugin configuration.
      if (isset($custom['config'])) {
        $values = [];
        foreach ($custom['config'] as $key => $value) {
          $values["{$parents}[content][{$custom_id}][config][{$key}]"] = $value;
        }
        $this->submitForm($values, "{$button}-content-{$custom_id}-submit");
      }
    }
  }

  /**
   * Perform the configuration of the design regions.
   *
   * @param string $parents
   *   The parents of the form element representing the design.
   * @param array $regions
   *   The regions.
   */
  protected function drupalSetupDesignRegions($parents, array $regions) {
    if (!is_array($regions)) {
      return;
    }

    $button = trim(preg_replace('/[\[\]]+/', '-', $parents), '-');
    foreach ($regions as $region_id => $region) {
      foreach ($region as $content) {
        $this->submitForm([
          "{$button}-regions-{$region_id}-addition-field" => $content,
        ], "{$button}-regions-{$region_id}-addition-submit");
      }
    }
  }

  /**
   * Perform setup of the design.
   *
   * @param string $parents
   *   The parents of the form element representing the design.
   * @param string $design
   *   The name of the design to be selected.
   */
  protected function drupalSetupDesign($parents, $design) {
    $button = trim(preg_replace('/[\[\]]+/', '-', $parents), '-');

    // Start the design form functionality.
    $this->submitForm(["{$parents}[design]" => $design], "{$button}-submit");
  }

}
