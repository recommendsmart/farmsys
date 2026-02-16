<?php

namespace Drupal\designs_view\Plugin\views\display_extender;

use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;

/**
 * Design display extender plugin.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "design",
 *   title = @Translation("Design display extender"),
 *   help = @Translation("Allow designs to occur on areas."),
 *   no_ui = TRUE
 * )
 */
class DesignDisplayExtender extends DisplayExtenderPluginBase {

  /**
   * Provide the key options for this plugin.
   */
  public function defineOptionsAlter(&$options) {
    $default = [
      'design' => '',
      'settings' => [],
      'content' => [],
      'regions' => [],
    ];

    $options['design'] = [
      'contains' => [
        'header' => ['default' => $default],
        'footer' => ['default' => $default],
        'empty' => ['default' => $default],
        'pager' => ['default' => $default],
      ],
    ];
  }

}
