<?php

namespace Drupal\basket;

/**
 * {@inheritdoc}
 */
class ViewsCartAlters {

  /**
   * ViewsViewAlter.
   */
  public function viewsViewAlter(&$vars) {
    $vars['attributes']['data-cartid'] = 'view_wrap-' . $vars['view']->id() . '-' . $vars['view']->current_display;
		if(!empty($vars['view']->args)) {
			$vars['attributes']['data-cartid'] .= '-'.implode('-', $vars['view']->args);
		}
    if (empty($vars['view']->result)) {
      $vars['empty'][] = [
        '#theme'        => 'basket_views_cart_empty',
      ];
    }
  }

}
