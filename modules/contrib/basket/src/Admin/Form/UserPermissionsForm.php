<?php

namespace Drupal\basket\Admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class UserPermissionsForm extends FormBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set permissionHandler.
   *
   * @var object
   */
  protected $permissionHandler;

  /**
   * Set moduleHandler.
   *
   * @var object
   */
  protected $moduleHandler;

  /**
   * Set moduleHandler.
   *
   * @var array
   */
  protected $providerList;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
    $this->permissionHandler = \Drupal::service('user.permissions');
    $this->moduleHandler = \Drupal::moduleHandler();

    $this->providerList = ['basket'];
    /*Alter*/
    \Drupal::moduleHandler()->alter('basket_translate_context', $this->providerList);
    /*END alter*/
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basket_permissions_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getRoles() {
    return \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $role_names = [];
    $role_permissions = [];
    $admin_roles = [];
    foreach ($this->getRoles() as $role_name => $role) {
      $role_names[$role_name] = $role->label();
      $role_permissions[$role_name] = $role->getPermissions();
      $admin_roles[$role_name] = $role->isAdmin();
    }
    $form['role_names'] = [
      '#type'         => 'value',
      '#value'        => $role_names,
    ];
    $form['permissions'] = [
      '#type'         => 'table',
      '#header'       => [
        $this->basket->Translate()->t('Permission'),
        $this->basket->Translate()->t('Group'),
      ],
      '#id'           => 'permissions',
      '#attributes'   => ['class' => ['permissions', 'js-permissions']],
      '#sticky'       => TRUE,
    ];
    foreach ($role_names as $name) {
      $form['permissions']['#header'][] = [
        'data'      => $name,
        'class'     => ['checkbox'],
      ];
    }
    $permissions = $this->permissionHandler->getPermissions();
    $permissions_by_provider = [];
    foreach ($permissions as $permission_name => $permission) {
      if (!in_array($permission['provider'], $this->providerList)) {
        continue;
      }
      $permissions_by_provider[$permission['provider']][$permission_name] = $permission;
    }
    foreach ($permissions_by_provider as $provider => $permissions) {
      $form['permissions'][$provider] = [
            [
              '#wrapper_attributes'   => [
                'colspan'               => count($role_names) + 2,
                'class'                 => ['module', 'not_hover'],
                'id'                    => 'module-' . $provider,
              ],
              '#type'                 => 'inline_template',
              '#template'             => '<h3>{{ label }}</h3>',
              '#context'              => [
                'label'                 => $this->moduleHandler->getName($provider),
              ],
            ],
      ];
      foreach ($permissions as $perm => $perm_item) {
        $form['permissions'][$perm]['description'] = [
          '#type'             => 'inline_template',
          '#template'         => '<div class="permission"><span class="title">{{ title }}</span>{% if sub_title %} "{{ sub_title }}"{% endif %}</div>',
          '#context'          => [
            'title'             => $this->basket->Translate($provider)->trans(trim($perm_item['title'])),
            'sub_title'         => !empty($perm_item['sub_title']) ? $this->basket->Translate($provider)->trans(trim($perm_item['sub_title'])) : NULL,
          ],
        ];
        $form['permissions'][$perm]['group'] = [
          '#type'             => 'inline_template',
          '#template'         => '<i>{{ group }}</i>',
          '#context'          => [
            'group'             => !empty($perm_item['group']) ? $this->basket->Translate()->trans(trim($perm_item['group'])) : '',
          ],
        ];
        // Show the permission description.
        foreach ($role_names as $rid => $name) {
          $form['permissions'][$perm][$rid] = [
            '#title'                => 'ON',
            '#wrapper_attributes'   => [
              'class'             => ['checkbox'],
            ],
            '#type'                 => 'checkbox',
            '#default_value'        => in_array($perm, $role_permissions[$rid]) ? 1 : 0,
            '#attributes'           => [
              'class'                 => [
                'rid-' . $rid, 'js-rid-' . $rid,
                'not_label',
              ],
            ],
            '#parents'              => [$rid, $perm],
          ];
          // Show a column of disabled but checked checkboxes.
          if ($admin_roles[$rid]) {
            $form['permissions'][$perm][$rid]['#disabled'] = TRUE;
            $form['permissions'][$perm][$rid]['#default_value'] = TRUE;
          }
        }
      }
    }
    $form['actions'] = [
      '#type'         => 'actions',
      'submit'        => [
        '#type'         => 'submit',
        '#value'        => $this->basket->Translate()->t('Save'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('role_names') as $role_name => $name) {
      user_role_change_permissions($role_name, (array) $form_state->getValue($role_name));
    }
    \Drupal::messenger()->addStatus($this->basket->Translate()->t('Settings saved.'));
  }

}
