<?php

namespace Drupal\basket;

use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * {@inheritdoc}
 */
class BasketExtraFields {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set nodeTypeSettings.
   *
   * @var array
   */
  protected $nodeTypeSettings;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function info(&$extra) {
    /*
     * Node
     */
    $settingsNodeTypes = $this->basket->getNodeTypes();
    if (!empty($settingsNodeTypes)) {
      foreach ($settingsNodeTypes as $row) {
        if (empty($row->extra_fields)) {
          continue;
        }
        foreach (unserialize($row->extra_fields) as $key => $rowExtra) {
          if (empty($rowExtra['on'])) {
            continue;
          }
          switch ($key) {
            case'add':
              $extra['node'][$row->type]['display']['basket_add'] = [
                'label'             => t('Add button', [], ['context' => 'basket']),
                'weight'            => 100,
                'visible'           => TRUE,
              ];
              break;

            case'add_params':
              $extra['node'][$row->type]['display']['basket_add_params'] = [
                'label'             => t('Selection of parameters', [], ['context' => 'basket']),
                'weight'            => 100,
                'visible'           => FALSE,
              ];
              break;
          }
        }
      }
    }
    $enabledServices = $this->basket->getSettings('enabled_services', NULL);
    if (!empty($enabledServices['delivery'])) {
      $extra['node']['basket_order']['form']['basket_delivery'] = [
        'label'             => t('Delivery', [], ['context' => 'basket']),
        'weight'            => 100,
        'visible'           => TRUE,
      ];
      $extra['node']['basket_order']['display']['basket_delivery'] = [
        'label'             => t('Delivery', [], ['context' => 'basket']),
        'weight'            => 100,
        'visible'           => TRUE,
      ];
      $extra['node']['basket_order']['display']['basket_delivery_address'] = [
        'label'             => t('Address', [], ['context' => 'basket']),
        'weight'            => 100,
        'visible'           => TRUE,
      ];
    }
    if (!empty($enabledServices['payment'])) {
      $extra['node']['basket_order']['form']['basket_payment'] = [
        'label'             => t('Payment', [], ['context' => 'basket']),
        'weight'            => 100,
        'visible'           => TRUE,
      ];
      $extra['node']['basket_order']['display']['basket_payment'] = [
        'label'             => t('Payment', [], ['context' => 'basket']),
        'weight'            => 100,
        'visible'           => TRUE,
      ];
    }
    /*
     * Users
     */
    $extra['user']['user']['form']['basket_user_percent'] = [
      'label'             => t('Individual user discount', [], ['context' => 'basket']),
      'weight'            => -100,
      'visible'           => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function nodeView(&$build, $entity, $display) {
    if ($addDisplay = $display->getComponent('basket_add')) {
      $settings = NULL;
      if(!empty($addDisplay['settings'])) {
        $settings = (object)[
          'extra_fields'  => [
            'add'           => [
              'on'            => TRUE,
              'text'          => $addDisplay['settings']['text'] ?? '',
              'count'         => $addDisplay['settings']['count'] ?? FALSE,
            ]
          ]
        ];
      }
      $build['basket_add'] = $this->basketAddGenerate($entity, 'node_view', $settings);
    }
    if ($display->getComponent('basket_add_params')) {
      $build['basket_add_params'] = \Drupal::service('BasketParams')->getField($entity, NULL, NULL, $display->getMode());
    }
    if ($display->getComponent('basket_delivery')) {
      $order = $this->basket->Orders(NULL, $entity->id())->load();
      if (!empty($order->delivery_id)) {
        $delivery = $this->basket->Term()->load($order->delivery_id);
        if (!empty($delivery)) {
          $build['basket_delivery'] = [
            '#title'        => $this->basket->Translate()->t('Delivery'),
            [
              '#markup'        => $this->basket->Translate()->trans($delivery->name),
            ],
          ];
        }
      }
      if (!empty($order->delivery_address)) {
        $delivery_address = unserialize($order->delivery_address);
        if (!empty($delivery_address)) {
          $build['basket_delivery_address'] = [
            '#title'        => $this->basket->Translate()->t('Address'),
            [
              '#markup'        => $delivery_address,
            ],
          ];
        }
      }
    }
    if ($display->getComponent('basket_payment')) {
      $order = $this->basket->Orders(NULL, $entity->id())->load();
      if (!empty($order->payment_id)) {
        $payment = $this->basket->Term()->load($order->payment_id);
        if (!empty($payment)) {
          $build['basket_payment'] = [
            '#title'        => $this->basket->Translate()->t('Payment'),
            [
              '#markup'        => $this->basket->Translate()->trans($payment->name),
            ],
          ];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function basketAddGenerate($entity, $type_view, $settings = NULL) {
    if (!\Drupal::currentUser()->hasPermission('basket add_button_access')) {
      return [];
    }
    if (!$entity->isPublished()) {
      return [];
    }
    if (empty($settings)) {
      $settings = $this->getNodeTypeSettings($entity->bundle());
    }
    // ---
    if (empty($settings->extra_fields['add']['on'])) {
      return [];
    }
    $views = [];
    if ( !empty($entity->view) ){
      $views = [
        'id'             => $entity->view->id(),
        'display'        => $entity->view->current_display,
        'args'           => implode('__', $entity->view->args),
        'dom_id'         => $entity->view->dom_id,
      ];
    }
    else if ( !empty($entity->view_id) ){
      $views = [
        'id'             => $entity->view_id,
        'display'        => $entity->view_current_display,
        'args'           => $entity->view_args ?? '',
        'dom_id'         => $entity->view_dom_id,
      ];
    }

    if (!empty($entity->basketAddParams)) {
      $views['popup'] = 'popup';
    }
    // ---
    $info = [
      'node'            => $entity,
      'type_view'       => $type_view,
      'allOptions'      => !empty($settings->extra_fields) ? $settings->extra_fields : [],
      'add'             => [
        'text'            => !empty($settings->extra_fields['add']['text']) ? $this->basket->Translate()->trans($settings->extra_fields['add']['text']) : $this->basket->Translate()->t('Buy'),
        'attributes'        => new Attribute([
          'href'                => 'javascript:void(0);',
          'class'               => ['addto_basket_button button'],
          'onclick'             => 'basket_ajax_link(this, \'' . Url::fromRoute('basket.pages', ['page_type' => 'api-add'])->toString() . '\')',
          'data-basket_node'    => $entity->id(),
        ]),
        'insert_values'        => [
          'nid'                 => $entity->id(),
          'post_type'           => 'add',
          'params'              => !empty($settings->setParams) ? $settings->setParams : [],
          'add_key'             => $entity->id() . '_' . implode('_', $views),
          'add_popup'           => !empty($entity->basketAddParams) ? 1 : 0,
        ],
      ],
    ];
    if (!empty($settings->extra_fields['add']['count'])) {
      $attr      = [
        'min'       => 1,
        'step'      => 1,
        'max'       => 999,
        'scale'     => 0,
      ];
      $productID = $entity->id();
      $params    = NULL;
      // Alter.
      \Drupal::moduleHandler()->alter('basket_count_input_attr', $attr, $productID, $params);
      // ---
      if (isset($attr['scale'])) {
        $attr['data-basket-scale'] = $attr['scale'];
        unset($attr['scale']);
      }
      else {
        $attr['data-basket-scale'] = 0;
      }
      $info += [
        'input'        => [
          'attributes'    => new Attribute($attr + [
            'type'            => 'number',
            'min'            => 1,
            'max'            => 999,
            'step'            => 1,
            'value'            => 1,
            'class'            => ['count_input'],
            'onblur'        => 'basket_input_count_format(this)',
          ]),
        ],
        'button'    => [
          'min'            => [
            'attributes'    => new Attribute([
              'href'            => 'javascript:void(0);',
              'class'            => ['arrow', 'min'],
              'onclick'        => 'basket_change_input_count(this, \'-\')',
            ]),
            'text'            => '-',
          ],
          'plus'            => [
            'attributes'    => new Attribute([
              'href'            => 'javascript:void(0);',
              'class'            => ['arrow', 'plus'],
              'onclick'        => 'basket_change_input_count(this, \'+\')',
            ]),
            'text'            => '+',
          ],
        ],
      ];
    }
    // Alter.
    \Drupal::moduleHandler()->alter('basket_add', $info);
    // ---
    $info['add']['attributes']['data-post'] = json_encode($info['add']['insert_values']);
    return [
      '#theme'        => 'basket_add',
      '#info'            => $info,
      '#attached'        => [
        'library'        => [
          'basket/basket.js',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeTypeSettings($nodeType) {
    if (!isset($this->nodeTypeSettings[$nodeType])) {
      $this->nodeTypeSettings[$nodeType] = \Drupal::database()->select('basket_node_types', 't')
        ->fields('t')
        ->condition('t.type', $nodeType)
        ->execute()->fetchObject();
      if (!empty($this->nodeTypeSettings[$nodeType]->extra_fields)) {
        $this->nodeTypeSettings[$nodeType]->extra_fields = unserialize($this->nodeTypeSettings[$nodeType]->extra_fields);
      }
    }
    return $this->nodeTypeSettings[$nodeType];
  }

  /**
   * {@inheritdoc}
   */
  public function userFormAlter(&$form, $form_state) {
    $form_display = \Drupal::service('entity_type.manager')->getStorage('entity_form_display')->load('user.user.default');
    if ($display = $form_display->getComponent('basket_user_percent')) {
      $entity = $form_state->getBuildInfo()['callback_object']->getEntity();
      $form['basket_user_percent'] = [
        '#type'            => 'select',
        '#title'        => $this->basket->Translate()->t('Individual user discount'),
        '#options'        => array_combine(range(0, 100), range(0, 100)),
        '#field_suffix'    => '%',
        '#disabled'        => !\Drupal::currentUser()->hasPermission('basket access_edit_user_percent'),
        '#default_value' => $this->basket->getCurrentUserPercent($entity->id()),
      ];
      $form['actions']['submit']['#submit'][] = [
        $this,
        'basketUserPercentSubmit',
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function basketUserPercentSubmit($form, $form_state) {
    $entity = $form_state->getBuildInfo()['callback_object']->getEntity();
    if (!empty($entity->id())) {
      $percent = $form_state->getValue('basket_user_percent');
      \Drupal::database()->merge('basket_user_percent')
        ->key([
          'uid'       => $entity->id(),
        ])
        ->fields([
          'percent'   => !empty($percent) ? $percent : 0,
        ])
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormAlter(&$form, $form_state) {
    if (empty($form['#extra'])) {
      return;
    }
    // ---
    $entityType = $form['#entity_type'];
    $bundle = $form['#bundle'];
    $viewMode = \Drupal::routeMatch()->getParameter('view_mode_name');
    // ---
    $entityViewDisplay = \Drupal::entityTypeManager()
        ->getStorage('entity_view_display')
        ->load($entityType . '.' . $bundle . '.' . $viewMode);
    if(empty($entityViewDisplay)) {
      $entityViewDisplay = \Drupal::entityTypeManager()
        ->getStorage('entity_view_display')
        ->create([
          'targetEntityType' => $entityType,
          'bundle' => $bundle,
          'mode' => $viewMode,
          'status' => TRUE,
        ]);
    }
    // ---
    $user_input = $form_state->getUserInput();
    foreach ($form['#extra'] as $name) {
      $settingsForm = NULL;
      $settings = [];
      $row = &$form['fields'][$name];
      $component = $entityViewDisplay->getComponent($name);
      // Check if field is not disabled.
      if (!$component) {
        if ($user_input && $user_input['fields'][$name]['region'] == 'hidden') {
          continue;
        }
        elseif ($row['region']['#default_value'] == 'hidden') {
          continue;
        }
        else {
          $settings = [];
        }
      }
      else {
        if ($user_input && $user_input['fields'][$name]['region'] == 'hidden') {
          continue;
        }
        else {
          $settings = isset($component['settings']) ? $component['settings'] : [];
          if (!$form_state->get($name)) {
            $form_state->set($name, $settings);
          }
          else {
            $settings = $form_state->get($name);
          }
          if ($form_state->get('plugin_settings_edit') && $form_state->get('plugin_settings_edit') != $name) {
            $settings = isset($user_input['fields'][$name]['settings_edit_form']['settings']) ? $user_input['fields'][$name]['settings_edit_form']['settings'] : $settings;
            $form_state->set($name, $settings);
          }
        }
      }
      $settingsForm = \Drupal::service('BasketExtraSettings')->getSettingsForm($name, [
        'bundle'        => $bundle,
        'mode'          => $viewMode,
        'settings'      => $settings
      ]);
      if(!empty($settingsForm)) {
        // Base button element for the various formatter settings actions.
        $base_button = [
          '#submit'     => ['::multistepSubmit'],
          '#ajax'       => [
            'callback'    => '::multistepAjax',
            'wrapper'     => 'field-display-overview-wrapper',
            'effect'      => 'fade',
          ],
          '#field_name' => $name,
        ];
        $settings_edit = $base_button + [
          '#type'         => 'image_button',
          '#attributes'   => [
            'class'         => ['field-plugin-settings-edit'],
            'alt'           => t('Edit'),
          ],
          '#src'          => 'core/misc/icons/787878/cog.svg',
          '#name'         => $name . '_settings_edit',
          '#op'           => 'edit',
          '#prefix'       => '<div class="field-plugin-settings-edit-wrapper">',
          '#suffix'       => '</div>',
        ];

        if ($form_state->get('plugin_settings_edit') == $name) {
          $value = $form_state->get($name);
          foreach ($settingsForm as $key => &$element) {
            if (!empty($value)) {
              $element['#default_value'] = isset($value[$key]) ? $value[$key] : $element['#default_value'];
            }
            else {
              $element['#default_value'] = isset($settings[$key]) ? $settings[$key] : $element['#default_value'];
            }
          }
          $row['plugin']['settings_edit_form'] = [];

          $row['plugin']['#cell_attributes'] = ['colspan' => 1];
          $row['plugin']['settings_edit_form'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['field-plugin-settings-edit-form']],
            '#parents' => ['fields', $name, 'settings_edit_form'],
            'label' => [
              '#markup' => t('Display settings:'),
            ],
            'settings' => $settingsForm,
            'actions' => [
              '#type' => 'actions',
              'save_settings' => $base_button + [
                '#type' => 'submit',
                '#button_type' => 'primary',
                '#name' => $name . '_plugin_settings_update',
                '#value' => t('Update'),
                '#op' => 'update',
              ],
              'cancel_settings' => $base_button + [
                '#type' => 'submit',
                '#name' => $name . '_plugin_settings_cancel',
                '#value' => t('Cancel'),
                '#op' => 'cancel',
                '#limit_validation_errors' => [['fields', $name, 'type']],
              ],
            ],
          ];
          $row['#attributes']['class'][] = 'field-plugin-settings-editing';
        }
        elseif ($form_state->get('plugin_settings_update') == $name) {
          $storage = $user_input['fields'][$name]['settings_edit_form']['settings'];
          $form_state->set($name, $storage);
          $row['settings_edit'] = $settings_edit;
          $row['settings_summary'] = [
            '#type'     => 'html_tag',
            '#tag'      => 'div',
            '#value'    => \Drupal::service('BasketExtraSettings')->getSettingsSummary($name, $settings, [
              'bundle'        => $bundle,
              'mode'          => $viewMode
            ]),
            '#attributes' => [
              'class'       => ['field-plugin-summary']
            ]
          ];
          $form_state->set('plugin_settings_update', NULL);
        }
        else {
          $row['settings_edit'] = $settings_edit;
          $row['settings_summary'] = [
            '#type'     => 'html_tag',
            '#tag'      => 'div',
            '#value'    => \Drupal::service('BasketExtraSettings')->getSettingsSummary($name, $settings, [
              'bundle'        => $bundle,
              'mode'          => $viewMode
            ]),
            '#attributes' => [
              'class'       => ['field-plugin-summary']
            ]
          ];
        }
        $form['actions']['submit']['#submit'][] = __CLASS__.'::extraFormSubmit';
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function extraFormSubmit($form, $form_state) {
    if (empty($form['#extra'])) {
      return;
    }
    $entityType = $form['#entity_type'];
    $bundle = $form['#bundle'];
    $viewMode = \Drupal::routeMatch()->getParameter('view_mode_name');

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_display */
    $viewDisplay = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load($entityType . '.' . $bundle . '.' . $viewMode);

    if ($viewDisplay) {
      foreach ($form['#extra'] as $name) {
        $row = $form['fields'][$name];

        // Don't process for hidden field.
        if ($row['region']['#value'] == 'hidden') {
          continue;
        }

        $component = $viewDisplay->getComponent($name);

        // Get settings from user input if user submitted display form
        // while editing extra field settings.
        if ($form_state->get('plugin_settings_update') == $name) {
          $user_input = $form_state->getUserInput();
          $settings = $user_input['fields'][$name]['settings_edit_form']['settings'];
        }
        // Get from storage.
        elseif ($form_state->get($name)) {
          $settings = $form_state->get($name);
        }
        // Get from display mode settings.
        else {
          $settings = $component['settings'];
        }

        $viewDisplay->setComponent($name,
          [
            'settings' => $settings,
          ] + $component)
          ->save();

      }
    }
  }
}
