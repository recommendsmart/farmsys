<?php

namespace Drupal\widget_type;

use Drupal\Core\Config\Config;

/**
 * Business logic for the configuration on the widget types.
 */
class WidgetTypeConfiguration {

  /**
   * The configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * WidgetTypeConfiguration constructor.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The configuration.
   */
  public function __construct(Config $config) {
    $this->config = $config;
  }

  /**
   * Check if a given widget type should ingest the assets to serve them.
   *
   * @param string $widget_machine_name
   *   The widget machine name.
   *
   * @return bool
   *   TRUE if Drupal should serve the widget. FALSE otherwise.
   */
  public function shouldIngestAssets(string $widget_machine_name): bool {
    $allowed = $this->config->get('download_assets.allowed_list');
    $disallowed = $this->config->get('download_assets.disallowed_list');
    if (empty($allowed) && empty($disallowed)) {
      return FALSE;
    }
    return empty($allowed)
      ? !\in_array($widget_machine_name, $disallowed, TRUE)
      : \in_array($widget_machine_name, $allowed, TRUE);
  }

}
