<?php

namespace Drupal\designs_preview\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Negotiates the theme for the designs preview page via the URL.
 */
class ThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $route_name = $route_match->getRouteName();
    return $route_name === 'designs_preview.theme_display' ||
      $route_name === 'designs_preview.design_display';
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    // We return exactly what was passed in, to guarantee that the page will
    // always be displayed using the theme displaying the pattern.
    return $route_match->getParameter('theme');
  }

}
