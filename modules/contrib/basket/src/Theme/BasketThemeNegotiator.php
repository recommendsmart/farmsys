<?php

namespace Drupal\basket\Theme;

use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * {@inheritdoc}
 */
class BasketThemeNegotiator implements ThemeNegotiatorInterface {
  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Creates a new AdminNegotiator instance.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   */
  public function __construct(AccountInterface $user) {
    $this->user = $user;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $possible_routes = [
      'basket.admin.pages',
    ];
    if (function_exists('_batch_current_set')) {
      $current_batch = _batch_current_set();
      if (!empty($current_batch['basket_batch'])) {
        return TRUE;
      }
    }
    return in_array($route_match->getRouteName(), $possible_routes) && $this->user->hasPermission('view the administration theme') ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    $theme = \Drupal::service('Basket')->getSettings('basket_theme', 'theme');
    return !empty($theme) ? $theme : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function systemThemeFormAlter(&$form, $form_state) {
    if (!empty($form['admin_theme']['admin_theme']['#options'])) {
      $form['admin_theme']['basket_theme'] = [
        '#type'                    => 'select',
        '#title'                => t('Basket theme'),
        '#options'            => $form['admin_theme']['admin_theme']['#options'],
        '#default_value' => \Drupal::service('Basket')->getSettings('basket_theme', 'theme'),
        '#parents'            => ['basket_theme'],
      ];
      $form['#submit'][] = __CLASS__ . '::basketThemeSubmit';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function basketThemeSubmit($form, $form_state) {
    \Drupal::service('Basket')->setSettings('basket_theme', 'theme', $form_state->getValue('basket_theme'));
  }

}
