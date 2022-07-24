<?php

/**
 * Clearing the cache to enable the service BasketAccess.
 */
function basket_post_update_add_access_service() {
	// Empty post-update hook.
}

/**
 * Replacement of component templates.
 */
function basket_post_update_settings_template() {
  $config = \Drupal::configFactory()->get('basket.setting.templates')->get();
  if(!empty($config)) {
    foreach ($config as &$item) {
      if(!empty($item['config']['template']) && is_string($item['config']['template'])) {
        foreach (['basket_order_name', 'basket_order_surname', 'basket_order_phone', 'basket_order_mail', 'basket_order_description'] as $fName) {
          $item['config']['template'] = str_replace('build.'.$fName, 'node.'.$fName.'.0.value', $item['config']['template']);
        }
      }
    }
    \Drupal::configFactory()->getEditable('basket.setting.templates')->setData($config)->save();
  }
}
