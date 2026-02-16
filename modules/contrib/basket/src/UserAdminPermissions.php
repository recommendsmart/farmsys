<?php

namespace Drupal\basket;

/**
 * {@inheritdoc}
 */
class UserAdminPermissions {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function formAlter(&$form, $form_state) {
    $providerList = ['basket'];
    /*Alter*/
    \Drupal::moduleHandler()->alter('basket_translate_context', $providerList);
    /*END alter*/
    $permissions = \Drupal::service('user.permissions')->getPermissions();
    $permissions_by_provider = [];
    foreach ($permissions as $permission_name => $permission) {
      if (!in_array($permission['provider'], $providerList)) {
        continue;
      }
      $permissions_by_provider[$permission['provider']][$permission_name] = $permission;
    }
    foreach ($permissions_by_provider as $provider => $permissions) {
      foreach ($permissions as $perm => $perm_item) {
        if (empty($form['permissions'][$perm])) {
          continue;
        }
        $form['permissions'][$perm]['description'] = [
          '#type'             => 'inline_template',
          '#template'         => '<div class="permission"><span class="title">{{ title }}</span>{% if sub_title %} "{{ sub_title }}"{% endif %}</div>',
          '#context'          => [
            'title'             => $this->basket->Translate($provider)->trans(trim($perm_item['title'])),
            'sub_title'         => !empty($perm_item['sub_title']) ? $this->basket->Translate($provider)->trans(trim($perm_item['sub_title'])) : NULL,
          ],
        ];
      }
    }
  }

}
