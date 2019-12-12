<?php

namespace Drupal\dashboards\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Dashboard theme.
 */
class ThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * Theme Manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Service constructor.
   *
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   Theme manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   Current user.
   */
  public function __construct(ThemeManagerInterface $theme_manager, ConfigFactoryInterface $configFactory, AccountInterface $currentUser) {
    $this->themeManager = $theme_manager;
    $this->config = $configFactory;
    $this->user = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if ($dashboard = $route_match->getParameter('dashboard')) {
      if (is_object($dashboard) && $dashboard->showAlwaysInFrontend()) {
        return FALSE;
      }
    }
    if ($this->user->isAnonymous() || !$this->user->hasPermission('View the administration theme')) {
      return FALSE;
    }

    if (in_array($route_match->getRouteName(), [
      'entity.dashboard.canonical',
      'layout_builder.dashboards.view',
      'layout_builder.dashboards_override.view',
    ])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->config->get('system.theme')->get('admin');
  }

}
