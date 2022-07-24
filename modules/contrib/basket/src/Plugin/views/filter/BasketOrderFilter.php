<?php

namespace Drupal\basket\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Core\Url;
use Drupal\views\Views;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Database\Query\Condition;

/**
 * Default implementation of the base filter plugin.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("basket_order_filter_fields")
 */
class BasketOrderFilter extends FilterPluginBase implements TrustedCallbackInterface {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->basket = \Drupal::service('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    if (!empty($this->view->args[0]) && $this->view->args[0] == 'is_delete') {
      $form['#attributes']['class'][] = 'hide_submit';
      return $form;
    }
    $form['combo_status'] = [
      '#type'            => 'hidden',
    ];
    $form['not_delete'] = [
      '#type'            => 'hidden',
    ];
    $form['filter_fields'] = [
      '#tree'            => TRUE,
      '#access'        => FALSE,
    ];
    foreach ($this->basket->getSettings('FilterOrders', 'fields') as $field_name => $field) {
      $form['filter_fields'][$field_name] = $field['form'];
      if (!empty($field['title'])) {
        $form['filter_fields'][$field_name]['#title'] = $this->basket->Translate()->trans($field['title']);
      }
      $form['filter_fields'][$field_name]['#weight'] = $field['weight'];
      if (!empty($form['filter_fields'][$field_name]['#multiple'])) {
        $form['filter_fields'][$field_name]['#attributes'] = [
          'data-texts'    => json_encode([
            'selectAllText'       => $this->basket->Translate()->t('Select all'),
            'allSelected'         => $this->basket->Translate()->t('All selected'),
            'countSelected'       => $this->basket->Translate()->t('# of %'),
            'noMatchesFound'      => $this->basket->Translate()->t('No matches found'),
          ]),
        ];
      }
      $form['filter_fields']['#access'] = TRUE;
      // Min / max.
      if (!empty($field['min_max'])) {
        $fieldTitle = !empty($form['filter_fields'][$field_name]['#title']) ? $form['filter_fields'][$field_name]['#title'] : '';
        $form['filter_fields'][$field_name]['#title'] = '';
        $form['filter_fields'][$field_name]['min']['#title'] = $fieldTitle . ', ' . $this->basket->Translate()->t('from');
        $form['filter_fields'][$field_name]['max']['#title'] = $fieldTitle . ', ' . $this->basket->Translate()->t('to');
      }
    }
    $form['#pre_render']['f'] = [$this, 'alterPreferredPreRender'];
    if (\Drupal::currentUser()->hasPermission('basket edit_orders_filter_fields_access')) {
      $form['settings_link'] = [
        '#type'         => 'inline_template',
        '#template'     => '<a href="javascript:void(0);" class="field_settings_link" onclick="{{onclick}}" title="{{title}}">{{ico|raw}}</a>',
        '#context'      => [
          'ico'           => $this->basket->getIco('settings_row.svg', 'base'),
          'title'         => $this->basket->Translate()->t('Filter field settings'),
          'onclick'       => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', [
            'page_type'   => 'api-orders_filter_settings',
          ])->toString() . '\')',
        ],
      ];
    }
    if (!$form['filter_fields']['#access']) {
      $form['#attributes']['class'][] = 'hide_submit';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    $accept = FALSE;
    $this->value = [];
    if (!empty($input['filter_fields']) && is_array($input['filter_fields'])) {
      foreach ($input['filter_fields'] as $key => $value) {
        if (!empty($value)) {
          $accept = TRUE;
          $this->value['filter_fields'][$key] = $value;
        }
      }
    }
    if (!empty($input['combo_status'])) {
      $accept = TRUE;
      $this->value['combo_status'] = json_decode($input['combo_status'], TRUE);
    }
    if (!empty($input['not_delete'])) {
      $accept = TRUE;
      $this->value['not_delete'] = TRUE;
    }
    return $accept;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    if (!empty($this->value)) {
      $join = Views::pluginManager('join')->createInstance('standard', [
        'type'           => 'INNER',
        'table'          => 'basket_orders',
        'field'          => 'nid',
        'left_table'     => 'node_field_data',
        'left_field'     => 'nid',
        'operator'       => '=',
      ]);
      $this->query->addRelationship('basket_orders', $join, 'node_field_data');
    }
    if (!empty($this->value['filter_fields'])) {
      $phoneField = $this->basket->getSettings('order_form', 'config.phone_mask.field');
      foreach ($this->value['filter_fields'] as $fieldName => $fieldValue) {
        if (strpos($fieldName, 'node:') !== FALSE) {
          list($entityKey, $fieldName_) = explode(':', $fieldName);
          if (\Drupal::database()->schema()->tableExists($entityKey . '__' . $fieldName_)) {
            $join = Views::pluginManager('join')->createInstance('standard', [
              'type'           => 'INNER',
              'table'          => $entityKey . '__' . $fieldName_,
              'field'          => 'entity_id',
              'left_table'     => 'node_field_data',
              'left_field'     => 'nid',
              'operator'       => '=',
            ]);
            $this->query->addRelationship($fieldName_, $join, 'node_field_data');
            if ($phoneField && $fieldName_ === $phoneField) {
              $this->phoneFilterQuery($this->query, $fieldName_, $fieldValue);
            }
            else {
              $this->query->addWhere('', $fieldName_ . '.' . $fieldName_ . '_value', '%' . $fieldValue . '%', 'LIKE');
            }
          }
        }
        else {
          switch ($fieldName) {
            case'delivery':
              $join = Views::pluginManager('join')->createInstance('standard', [
                'type'           => 'INNER',
                'table'          => 'basket_orders_delivery',
                'field'          => 'nid',
                'left_table'     => 'node_field_data',
                'left_field'     => 'nid',
                'operator'       => '=',
              ]);
              $this->query->addRelationship('basket_orders_delivery', $join, 'node_field_data');
              $this->query->addWhere('', 'basket_orders_delivery.did', $fieldValue, (is_array($fieldValue) ? 'in' : '='));
              break;

            case'payment':
              $join = Views::pluginManager('join')->createInstance('standard', [
                'type'           => 'INNER',
                'table'          => 'basket_orders_payment',
                'field'          => 'nid',
                'left_table'     => 'node_field_data',
                'left_field'     => 'nid',
                'operator'       => '=',
              ]);
              $this->query->addRelationship('basket_orders_payment', $join, 'node_field_data');
              $this->query->addWhere('', 'basket_orders_payment.pid', $fieldValue, (is_array($fieldValue) ? 'in' : '='));
              break;

            case'order_created':
              if (!empty($fieldValue['min'])) {
                $this->query->addWhere('', 'node_field_data.created', strtotime($fieldValue['min'] . ' 00:00:00'), '>=');
              }
              if (!empty($fieldValue['max'])) {
                $this->query->addWhere('', 'node_field_data.created', strtotime($fieldValue['max'] . ' 23:59:59'), '<=');
              }
              break;

            default:
              $this->query->addWhere('', 'basket_orders.' . $fieldName, $fieldValue, (is_array($fieldValue) ? 'in' : '='));
              break;
          }
        }
      }
    }
    if (!empty($this->value['combo_status']['status'])) {
      $this->query->addWhere('', 'basket_orders.status', $this->value['combo_status']['status'], 'in');
    }
    if (!empty($this->value['not_delete'])) {
      $this->query->addWhere('', 'basket_orders.is_delete', NULL, 'IS NULL');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    return 'Basket orders filter';
  }

  /**
   * {@inheritdoc}
   */
  public static function apiResponseAlter(&$response) {
    if (!\Drupal::currentUser()->hasPermission('basket edit_orders_filter_fields_access')) {
      return FALSE;
    }
    \Drupal::getContainer()->get('BasketPopup')->openModal(
      $response,
      \Drupal::getContainer()->get('Basket')->translate()->t('Filter field settings'),
      \Drupal::formBuilder()->getForm(new SettingsOrdersFilterFields()),
      [
        'width' => 1000,
        'class' => ['basket_settings_popup'],
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    $callbacks = parent::trustedCallbacks();
    $callbacks[] = 'alterPreferredPreRender';
    return $callbacks;
  }

  /**
   * Pre render form.
   */
  public function alterPreferredPreRender(array $element) {
    $element['actions']['submit']['#value'] = \Drupal::getContainer()->get('Basket')->Translate()->t('Filter');
    return $element;
  }

  public function phoneFilterQuery($query, $fieldName_, $fieldValue) {
    $fieldValue = preg_replace('/[^0-9]/', '', $fieldValue);
    $phone_replaces = ['+','(',')','-',' '];
    $replace = $fieldName_ . '.' . $fieldName_ . '_value';
    foreach ($phone_replaces as $replaceKey) {
      $replace = 'REPLACE('.$replace.', \''.$replaceKey.'\', \'\')';
    }
    $cond = new Condition('AND');
    $cond->where($replace . ' LIKE \'%'. \Drupal::database()->escapeLike(trim($fieldValue)).'%\'');
    $query->addWhere('', $cond);
  }

}

/**
 * {@inheritdoc}
 */
class SettingsOrdersFilterFields extends FormBase {

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
    $this->basket = \Drupal::getContainer()->get('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basket_settings_orders_filter_fields';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form += [
      '#prefix'        => '<div id="basket_settings_orders_filter_fields_ajax_wrap" class="basket_table_wrap">',
      '#suffix'        => '</div>',
    ];
    $form['config'] = [
      '#type'            => 'table',
      '#header'        => [
        '',
        '',
        $this->basket->translate()->t('Field'),
        $this->basket->translate()->t('Name in the form'),
        $this->basket->translate()->t('Multi value'),
        '',
      ],
      '#tabledrag'    => [
      [
        'action'        => 'order',
        'relationship'  => 'sibling',
        'group'         => 'group-order-weight',
      ],
      ],
    ];
    $settings = $this->basket->getSettings('FilterOrders', 'fields');
    foreach ($this->getFilterFieldsBaseSettings() as $fieldName => $fieldInfo) {
      $form['config'][$fieldName] = [
        '#attributes'    => [
          'class'            => ['draggable'],
        ],
        '#weight'        => !empty($settings[$fieldName]['weight']) ? $settings[$fieldName]['weight'] : 10000,
        'handle'        => [],
        'on'            => [
          '#type'            => 'checkbox',
          '#title'        => 'on',
          '#attributes'    => [
            'class'            => ['not_label'],
          ],
          '#default_value' => !empty($settings[$fieldName]),
        ],
        'title'            => [
          '#markup'        => '<b>' . $fieldInfo['title'] . '</b>',
          '#default_value' => !empty($settings[$fieldName]['title']) ? $settings[$fieldName]['title'] : '',
        ],
        'form_name'        => [
          '#type'            => 'textfield',
          '#default_value' => $fieldInfo['title'],
          '#states'        => [
            'visible'        => [
              'input[name="config[' . $fieldName . '][on]"]' => ['checked' => TRUE],
            ],
          ],
        ],
        'multiple'        => [],
        'weight'        => [
          '#type'            => 'number',
          '#attributes'    => [
            'class'            => ['group-order-weight'],
          ],
          '#default_value' => !empty($settings[$fieldName]['weight']) ? $settings[$fieldName]['weight'] : 1,
        ],
      ];
      if (isset($fieldInfo['form']['#multiple'])) {
        $form['config'][$fieldName]['multiple'] += [
          '#type'            => 'checkbox',
          '#title'        => 'on',
          '#attributes'    => [
            'class'            => ['not_label'],
          ],
          '#default_value' => !empty($settings[$fieldName]['form']['#multiple']),
          '#states'        => [
            'visible'        => [
              'input[name="config[' . $fieldName . '][on]"]' => ['checked' => TRUE],
            ],
          ],
        ];
      }
    }
    uasort($form['config'], 'Drupal\Component\Utility\SortArray::sortByWeightProperty');
    $form['actions'] = [
      '#type'            => 'actions',
      'submit'        => [
        '#type'            => 'submit',
        '#value'        => $this->basket->translate()->t('Save'),
        '#ajax'            => [
          'wrapper'        => 'basket_settings_orders_filter_fields_ajax_wrap',
          'callback'        => [$this, 'ajaxSubmit'],
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $setValues = [];
    if (!empty($values['config'])) {
      $fields = $this->getFilterFieldsBaseSettings();
      foreach ($values['config'] as $field_name => $config) {
        if (empty($config['on'])) {
          continue;
        }
        $setValues[$field_name] = $fields[$field_name];
        $setValues[$field_name]['title'] = trim($config['form_name']);
        $setValues[$field_name]['weight'] = $config['weight'];
        if (!empty($config['multiple'])) {
          $setValues[$field_name]['form']['#multiple'] = TRUE;
        }
      }
    }
    $this->basket->setSettings('FilterOrders', 'fields', $setValues);
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxSubmit($form, $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand('body', 'append', ['<script>location.reload();</script>']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilterFieldsBaseSettings() {
    $fields = [
      'id'            => [
        'title'            => 'Order ID',
        'form'            => [
          '#type'            => 'number',
          '#min'            => 1,
          '#step'            => 1,
          '#attributes'    => [
            'autocomplete'    => 'off',
          ],
        ],
      ],
      'currency'        => [
        'title'            => 'Currency',
        'form'            => [
          '#type'            => 'select',
          '#options'        => $this->basket->currency()->getOptions(),
          '#multiple'        => FALSE,
          '#empty_option'    => '',
        ],
      ],
      'status'        => [
        'title'            => 'Status',
        'form'            => [
          '#type'            => 'select',
          '#options'        => $this->basket->term()->getOptions('status'),
          '#multiple'        => FALSE,
          '#empty_option'    => '',
        ],
      ],
      'fin_status'        => [
        'title'            => 'Financial status',
        'form'            => [
          '#type'            => 'select',
          '#options'        => $this->basket->term()->getOptions('fin_status'),
          '#multiple'        => FALSE,
          '#empty_option'    => '',
        ],
      ],
      'payment'            => [
        'title'            => 'Payment',
        'form'            => [
          '#type'            => 'select',
          '#options'        => $this->basket->term()->getOptions('payment'),
          '#multiple'        => FALSE,
          '#empty_option'    => '',
        ],
      ],
      'delivery'            => [
        'title'            => 'Delivery',
        'form'            => [
          '#type'            => 'select',
          '#options'        => $this->basket->term()->getOptions('delivery'),
          '#multiple'        => FALSE,
          '#empty_option'    => '',
        ],
      ],
      'order_created'        => [
        'title'            => 'Date',
        'min_max'        => TRUE,
        'form'            => [
          '#type'            => 'item',
          '#wrapper_attributes' => ['class' => ['items_combine']],
          'min'            => [
            '#type'            => 'date',
          ],
          'arrow'            => [
            '#type'            => 'item',
            '#wrapper_attributes' => ['class' => ['arrow']],
          ],
          'max'            => [
            '#type'            => 'date',
          ],
        ],
      ],
    ];
    foreach (\Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'basket_order') as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
        switch ($field_definition->getName()) {
          case'status':
          case'promote':
          case'uid':
          case'sticky':
          case'menu_link':
          case'created':
          case'path':
          case'metatag':
          case'changed':
          case'field_meta_tags':
          case'field_yoast_seo':
          case'title':
            break;

          default;
            $fields['node:' . $field_name] = [
              'title'        => $field_definition->getLabel(),
              'form'        => [
                '#type'            => 'textfield',
                '#attributes'    => [
                  'autocomplete'    => 'off',
                ],
              ],
            ];
            switch ($field_definition->getType()) {
              case'boolean':
                $fields['node:' . $field_name]['form'] = [
                  '#type'            => 'select',
                  '#options'        => [
                    '1'                => t('Yes'),
                    '0'                => t('No'),
                  ],
                  '#empty_option'    => '',
                ];
                break;
            }
            break;
        }
      }
    }
    return $fields;
  }

}
