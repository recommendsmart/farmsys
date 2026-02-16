<?php

namespace Drupal\flow\Helpers;

/**
 * Trait for Flow-related components that support third party settings.
 */
trait ThirdPartySettingsTrait {

  /**
   * Third party settings.
   *
   * An array of key/value pairs keyed by provider.
   *
   * @var array
   */
  protected array $thirdPartySettings = [];

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySetting($provider, $key, $default = NULL) {
    return $this->thirdPartySettings[$provider][$key] ?? $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySettings($provider) {
    return $this->thirdPartySettings[$provider] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function setThirdPartySetting($provider, $key, $value) {
    $this->thirdPartySettings[$provider][$key] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unsetThirdPartySetting($provider, $key) {
    unset($this->thirdPartySettings[$provider][$key]);
    // If the third party is no longer storing any information, completely
    // remove the array holding the settings for this provider.
    if (empty($this->thirdPartySettings[$provider])) {
      unset($this->thirdPartySettings[$provider]);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartyProviders() {
    return array_keys($this->thirdPartySettings);
  }

}
