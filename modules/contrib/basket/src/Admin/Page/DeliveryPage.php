<?php

namespace Drupal\basket\Admin\Page;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * {@inheritdoc}
 */
class DeliveryPage {

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
  public function table() {
    $elements = [
      'view'      => [
        '#prefix'       => '<div class="basket_table_wrap">',
        '#suffix'       => '</div>',
        [
          '#prefix'       => '<div class="b_content">',
          '#suffix'       => '</div>',
          'form'          => \Drupal::formBuilder()->getForm(new DeliveryListSettingsForm()),
        ],
      ],
      'CreateLink'    => [
        '#type'         => 'inline_template',
        '#template'     => '<a href="javascript:void(0);" onclick="{{onclick}}" data-post="{{post}}" id="CreateLink">+ {{text}}</a>',
        '#context'      => [
          'text'          => $this->basket->Translate()->t('Create'),
          'onclick'       => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-create_delivery'])->toString() . '\')',
          'post'          => json_encode([
            'type'          => 'delivery',
          ]),
        ],
      ],
    ];
    $elements[] = $this->basket->getClass('Drupal\basket\Admin\Page\Blocks')->deliverys();
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function apiResponseAlter(&$response, $pageSubtype = NULL) {
    switch ($pageSubtype) {
      case'create_delivery':
      case'edit_delivery':
        $tid = !empty($_POST['tid']) ? $_POST['tid'] : NULL;
        \Drupal::service('BasketPopup')->openModal(
          $response,
          $this->basket->Translate()->t('Create'),
          \Drupal::formBuilder()->getForm(new DeliverySettingsForm($tid)),
          [
            'width' => 960,
            'class' => [],
          ]
        );
        break;
    }
  }

}
/**
 * {@inheritdoc}
 */
class DeliveryListSettingsForm extends FormBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set services.
   *
   * @var object
   */
  protected $services;

  /**
   * Set servicesSettings.
   *
   * @var array
   */
  protected $servicesSettings;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
    $this->services = \Drupal::service('BasketDelivery')->getDefinitions();
    $this->servicesSettings = $this->basket->getSettings('delivery_services', NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basket_delivery_list_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form += [
      '#prefix'       => '<div id="basket_delivery_list_settings_form">',
      '#suffix'       => '</div>',
    ];
    $form['delivery_enabled'] = [
      '#type'         => 'checkbox',
      '#title'        => $this->basket->Translate()->t('Enable service'),
      '#default_value' => $this->basket->getSettings('enabled_services', 'delivery'),
    ];
    $form['delivery_default'] = [
      '#type'             => 'radio',
      '#title'            => $this->basket->Translate()->t('The default is empty'),
      '#attributes'       => [
        'checked'           => $this->basket->getSettings('active_services', 'delivery_default.0'),
      ],
      '#parents'          => ['active_default'],
      '#return_value'     => 0,
    ];
    $form['config_wrap'] = [
      '#type'         => 'container',
      '#states'       => [
        'visible'       => [
          'input[name="delivery_enabled"]' => ['checked' => TRUE],
        ],
      ],
      'config'        => [
        '#type'         => 'table',
        '#header'       => [
          '',
          $this->basket->Translate()->t('Active'),
          $this->basket->Translate()->t('Name'),
          $this->basket->Translate()->t('Default'),
          $this->basket->Translate()->t('Price'),
          $this->basket->Translate()->t('Service'),
          $this->basket->Translate()->t('Settings'),
          '',
          '',
        ],
        '#tabledrag'    => [
          [
            'action'        => 'order',
            'relationship'  => 'sibling',
            'group'         => 'group-order-weight',
          ],
        ],
        '#empty'        => $this->basket->Translate()->t('The list is empty.'),
      ],
    ];
    if (!empty($results = $this->basket->Term()->tree('delivery'))) {
      foreach ($results as $result) {
        $form['config_wrap']['config'][$result->id] = [
          '#attributes'     => [
            'class'           => ['draggable'],
          ],
          '#weight'         => $result->weight,
          'handle'          => [
            '#wrapper_attributes' => [
              'class'         => ['tabledrag-handle-td'],
            ],
          ],
          'active'        => [
            '#type'         => 'checkbox',
            '#title'        => 'ON',
            '#attributes'   => [
              'class'         => ['not_label'],
            ],
            '#parents'      => ['active_config', $result->id],
            '#default_value' => $this->basket->getSettings('active_services', 'delivery.' . $result->id),
          ],
          'id'            => [
            '#type'         => 'item',
            '#markup'       => $this->basket->Translate()->trans(trim($result->name)),
            '#value'        => $result->id,
            '#field_suffix' => $this->basket->Translate()->getTranslateLink(trim($result->name)),
          ],
          'default'       => [
            '#type'         => 'radio',
            '#title'        => 'ON',
            '#attributes'   => [
              'class'         => ['not_label'],
              'checked'       => $this->basket->getSettings('active_services', 'delivery_default.' . $result->id),
            ],
            '#parents'      => ['active_default'],
            '#return_value' => $result->id,
          ],
          'price'         => [
            '#type'         => 'inline_template',
            '#template'     => '{% if currency.name and term.delivery_sum %}{{ term.delivery_sum|number_format(2, \',\', \' \') }} {{ basket_t(currency.name) }}{% else %}- - -{% endif %}',
            '#context'      => [
              'term'          => $result,
              'currency'      => !empty($result->delivery_currency) ? $this->basket->Currency()->load($result->delivery_currency) : NULL,
            ],
          ],
          'service'       => [
            '#type'         => 'inline_template',
            '#template'     => '{% if id %}<b>{{ basket_t(name, {}, provider) }}</b>{% endif %}',
            '#context'      => !empty($this->servicesSettings[$result->id]) && !empty($this->services[$this->servicesSettings[$result->id]]) ? $this->services[$this->servicesSettings[$result->id]] : [],
          ],
          'settings'      => $this->getSettingsInfo($result),
          'weight'        => [
            '#type'         => 'number',
            '#attributes'   => [
              'class'         => ['group-order-weight'],
            ],
            '#default_value' => $result->weight,
          ],
          'links'         => [
            '#type'         => 'inline_template',
            '#template'     => '<a href="javascript:void(0);" class="settings_row tooltipster_init">{{ico|raw}}</a>
                            <div class="tooltipster_content">
                                <a href="javascript:void(0);" class="button--link" onclick="{{link[0].onclick}}" data-post="{{link[0].post}}"><span class="ico">{{ link[0].ico|raw }}</span> {{ link[0].text }}</a><br/>
                                <a href="javascript:void(0);" class="button--link" onclick="{{link[1].onclick}}" data-post="{{link[1].post}}"><span class="ico">{{ link[1].ico|raw }}</span> {{ link[1].text }}</a>
                            </div>',
            '#context'      => [
              'ico'           => $this->basket->getIco('settings_row.svg', 'base'),
              'link'          => [
                [
                  'text'          => $this->basket->Translate()->t('Edit'),
                  'ico'           => $this->basket->getIco('edit.svg'),
                  'onclick'       => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-edit_delivery'])->toString() . '\')',
                  'post'          => json_encode([
                    'type'          => $result->type,
                    'tid'           => $result->id,
                  ]),
                ], [
                  'text'          => $this->basket->Translate()->t('Delete'),
                  'ico'           => $this->basket->getIco('trash.svg'),
                  'onclick'       => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-delete_term'])->toString() . '\')',
                  'post'          => json_encode([
                    'delete_tid'    => $result->id,
                  ]),
                ],
              ],
            ],
            '#wrapper_attributes' => [
              'class'             => ['td_settings_row'],
            ],
          ],
        ];
      }
    }
    $form['delivery_widget'] = [
      '#type'         => 'select',
      '#title'        => t('Widget'),
      '#options'      => [
        'select'        => t('Select list'),
        'radios'        => t('Radios'),
      ],
      '#default_value' => $this->basket->getSettings('enabled_services', 'delivery_widget'),
      '#states'       => [
        'visible'       => [
          'input[name="delivery_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['actions'] = [
      '#type'         => 'actions',
      'submit'        => [
        '#type'         => 'submit',
        '#value'        => $this->basket->Translate()->t('Save'),
        '#ajax'         => [
          'wrapper'       => 'basket_delivery_list_settings_form',
          'callback'      => '::ajaxSubmit',
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  private function getSettingsInfo($result) {
    $items = [];
    if (!empty($this->servicesSettings[$result->id]) && !empty($system = $this->services[$this->servicesSettings[$result->id]])) {

      $items = \Drupal::service('BasketDeliverySettings')->getSettingsInfoList($result->id, $system);

      if (!empty($system['provider'])) {
        $module_info = \Drupal::service('extension.list.module')->getExtensionInfo($system['provider']);
        if (!empty($module_info['configure'])) {
          $items[] = [
            '#type'         => 'inline_template',
            '#template'     => '<a href="{{ url }}" target="_blank" class="button--link target">{{ text }}</a>',
            '#context'      => [
              'text'          => $this->basket->Translate()->t('Settings page'),
              'url'           => Url::fromRoute($module_info['configure'])->toString(),
            ],
          ];
        }
      }
    }
    // Alter
    \Drupal::moduleHandler()->alter('delivery_settings_info', $items, $result);
    // ---
    return [
      '#theme'            => 'item_list',
      '#list_type'        => 'ul',
      '#wrapper_attributes' => [
        'class'             => ['settings_list_block'],
      ],
      '#items'            => $items,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // enabled_services.
    $this->basket->setSettings(
      'enabled_services',
      'delivery',
      $form_state->getValue('delivery_enabled')
    );
    $this->basket->setSettings(
      'enabled_services',
      'delivery_widget',
      $form_state->getValue('delivery_widget')
    );
    // Set active settings.
    $this->basket->setSettings(
      'active_services',
      'delivery',
      $form_state->getValue('active_config')
    );
    // Set active default.
    $this->basket->setSettings(
      'active_services',
      'delivery_default',
      [$form_state->getValue('active_default') => TRUE]
    );
    // Weight.
    foreach ($form_state->getValue('config') as $row) {
      \Drupal::database()->update('basket_terms')
        ->fields([
          'weight'    => $row['weight'],
        ])
        ->condition('id', $row['id'])
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand('.messages, .tabledrag-changed', 'remove', []));
    $response->addCommand(new InvokeCommand('.drag-previous', 'removeClass', ['drag-previous']));
    $response->addCommand(new InvokeCommand(NULL, 'NotyGenerate', [
      'status',
      \Drupal::service('Basket')->Translate()->t('Settings saved.'),
    ]));
    return $response;
  }

}
/**
 * {@inheritdoc}
 */
class DeliverySettingsForm extends FormBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set term.
   *
   * @var object
   */
  protected $term;

  /**
   * Set termServiceDef.
   *
   * @var string
   */
  protected $termServiceDef;

  /**
   * {@inheritdoc}
   */
  public function __construct($editTid = NULL) {
    $this->basket = \Drupal::service('Basket');
    $this->term = !empty($editTid) ? \Drupal::service('Basket')->Term()->load($editTid) : NULL;
    if (!empty($this->term->id)) {
      $this->termServiceDef = $this->basket->getSettings('delivery_services', $this->term->id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basket_delivery_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form += [
      '#prefix'       => '<div id="basket_delivery_settings_form_ajax_wrap">',
      '#suffix'       => '</div>',
      'status_messages' => [
        '#type'         => 'status_messages',
      ],
    ];
    $form['tid'] = [
      '#type'         => 'hidden',
      '#value'        => !empty($this->term->id) ? $this->term->id : NULL,
    ];
    $form['name'] = [
      '#type'         => 'textfield',
      '#title'        => $this->basket->Translate()->trans('Name') . ' EN',
      '#required'     => TRUE,
      '#default_value' => !empty($this->term->name) ? $this->term->name : '',
    ];
    $form['delivery_sum'] = [
      '#type'         => 'number',
      '#title'        => $this->basket->Translate()->t('Shipping cost'),
      '#min'          => 0,
      '#step'         => 0.01,
      '#default_value' => !empty($this->term->delivery_sum) ? $this->term->delivery_sum : 0,
    ];
    $form['delivery_currency'] = [
      '#type'         => 'select',
      '#title'        => $this->basket->Translate()->t('Currency'),
      '#options'      => $this->basket->Currency()->getOptions(),
      '#default_value' => !empty($this->term->delivery_currency) ? $this->term->delivery_currency : 0,
    ];
    $services = [];
    if (!empty($deliverys = \Drupal::service('BasketDelivery')->getDefinitions())) {
      foreach ($deliverys as $delivery) {
        $services[$delivery['id']] = $this->basket->Translate(trim($delivery['provider']))->trans(trim($delivery['name']));
      }
    }
    $form['service'] = [
      '#type'         => 'select',
      '#title'        => $this->basket->Translate()->t('Service'),
      '#options'      => $services,
      '#empty_option' => $this->basket->Translate()->t('Not specified'),
      '#default_value' => $this->termServiceDef,
    ];
    /*Service alter*/
    \Drupal::service('BasketDeliverySettings')->deliverySettingsFormAlter($form, $form_state);
    /*---*/
    $form['actions'] = [
      '#type'         => 'actions',
      'submit'        => [
        '#type'         => 'submit',
        '#value'        => $this->basket->Translate()->t('Save'),
        '#ajax'         => [
          'wrapper'       => 'basket_delivery_settings_form_ajax_wrap',
          'callback'      => '::ajaxSubmit',
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
    if (!empty($values['tid'])) {
      \Drupal::database()->update('basket_terms')
        ->fields([
          'name'              => trim($values['name']),
          'delivery_sum'      => !empty($values['delivery_sum']) ? $values['delivery_sum'] : NULL,
          'delivery_currency' => !empty($values['delivery_currency']) ? $values['delivery_currency'] : NULL,
        ])
        ->condition('id', $values['tid'])
        ->execute();
    }
    else {
      $values['tid'] = \Drupal::database()->insert('basket_terms')
        ->fields([
          'type'      => 'delivery',
          'name'      => trim($values['name']),
          'weight'    => -100,
          'delivery_sum'      => !empty($values['delivery_sum']) ? $values['delivery_sum'] : NULL,
          'delivery_currency' => !empty($values['delivery_currency']) ? $values['delivery_currency'] : NULL,
        ])
        ->execute();
    }
    /*Update service info*/
    $this->basket->setSettings(
      'delivery_services',
      $values['tid'],
      $form_state->getValue('service')
    );
    $form_state->setValue('tid', $values['tid']);
  }

  /**
   * {@inheritdoc}
   */
  public static function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    if ($form_state->isSubmitted() && $form_state->getErrors()) {
      return $form;
    }
    else {
      $response = new AjaxResponse();
      $response->addCommand(new InvokeCommand('body', 'append', ['<script>location.reload();</script>']));
      $response->addCommand(new InvokeCommand(NULL, 'NotyGenerate', [
        'status',
        \Drupal::service('Basket')->Translate()->t('Settings saved.'),
      ]));
      return $response;
    }
  }

}
