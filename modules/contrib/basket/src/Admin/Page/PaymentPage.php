<?php

namespace Drupal\basket\Admin\Page;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Url;

/**
 * {@inheritdoc}
 */
class PaymentPage {

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
    [
      '#prefix'     => '<div class="basket_table_wrap">',
      '#suffix'     => '</div>',
      [
        '#prefix'     => '<div class="b_content">',
        '#suffix'     => '</div>',
          [
            \Drupal::formBuilder()->getForm(new PaymentListSettingsForm()),
          ],
      ],
    ],
      'CreateLink'    => [
        '#type'         => 'inline_template',
        '#template'     => '<a href="javascript:void(0);" onclick="{{onclick}}" data-post="{{post}}" id="CreateLink">+ {{text}}</a>',
        '#context'      => [
          'text'          => $this->basket->translate()->t('Create'),
          'onclick'       => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-create_payment'])->toString() . '\')',
          'post'          => json_encode([
            'type'          => 'payment',
          ]),
        ],
      ],
    ];
    $elements[] = $this->basket->getClass('Drupal\basket\Admin\Page\Blocks')->payments();
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function apiResponseAlter(&$response, $pageSubtype = NULL) {
    switch ($pageSubtype) {
      case'create_payment':
      case'edit_payment':
        $tid = !empty($_POST['tid']) ? $_POST['tid'] : NULL;
        \Drupal::service('BasketPopup')->openModal(
          $response,
          $this->basket->translate()->t('Create'),
          \Drupal::formBuilder()->getForm(new PaymentSettingsForm($tid)),
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
class PaymentListSettingsForm extends FormBase {

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
    $this->services = \Drupal::service('BasketPayment')->getDefinitions();
    $this->servicesSettings = $this->basket->getSettings('payment_services', NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basket_payment_list_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form += [
      '#prefix'     => '<div id="basket_payment_list_settings_form">',
      '#suffix'     => '</div>',
    ];
    $form['payment_enabled'] = [
      '#type'       => 'checkbox',
      '#title'      => $this->basket->translate()->t('Enable service'),
      '#default_value' => $this->basket->getSettings('enabled_services', 'payment'),
    ];
    $form['payment_default'] = [
      '#type'       => 'radio',
      '#title'      => $this->basket->translate()->t('The default is empty'),
      '#attributes' => [
        'checked'     => $this->basket->getSettings('active_services', 'payment_default.0'),
      ],
      '#parents'    => ['active_default'],
      '#return_value' => 0,
    ];
    $form['config_wrap'] = [
      '#type'       => 'container',
      '#states'     => [
        'visible'     => [
          'input[name="payment_enabled"]' => ['checked' => TRUE],
        ],
      ],
      'config'      => [
        '#type'       => 'table',
        '#header'     => [
          '',
          $this->basket->translate()->t('Active'),
          $this->basket->translate()->t('Name'),
          $this->basket->translate()->t('Default'),
          $this->basket->translate()->t('Service'),
          $this->basket->translate()->t('Settings'),
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
        '#empty'        => $this->basket->translate()->t('The list is empty.'),
      ],
    ];
    if (!empty($results = $this->basket->term()->tree('payment'))) {
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
          'active'          => [
            '#type'           => 'checkbox',
            '#title'          => 'ON',
            '#attributes'     => [
              'class'           => ['not_label'],
            ],
            '#parents'        => ['active_config', $result->id],
            '#default_value'  => $this->basket->getSettings('active_services', 'payment.' . $result->id),
          ],
          'id'              => [
            '#type'           => 'item',
            '#markup'         => $this->basket->translate()->trans(trim($result->name)),
            '#value'          => $result->id,
            '#field_suffix'   => $this->basket->translate()->getTranslateLink(trim($result->name)),
          ],
          'default'         => [
            '#type'           => 'radio',
            '#title'          => 'ON',
            '#attributes'     => [
              'class'           => ['not_label'],
              'checked'         => $this->basket->getSettings('active_services', 'payment_default.' . $result->id),
            ],
            '#parents'        => ['active_default'],
            '#return_value'   => $result->id,
          ],
          'service'         => [
            '#type'           => 'inline_template',
            '#template'       => '{% if id %}<b>{{ basket_t(name, {}, provider) }}</b>{% endif %}',
            '#context'        => !empty($this->servicesSettings[$result->id]) && !empty($this->services[$this->servicesSettings[$result->id]]) ? $this->services[$this->servicesSettings[$result->id]] : [],
          ],
          'settings'        => $this->getSettingsInfo($result),
          'weight'          => [
            '#type'           => 'number',
            '#attributes'     => [
              'class'           => ['group-order-weight'],
            ],
            '#default_value'  => $result->weight,
          ],
          'links'           => [
            '#type'           => 'inline_template',
            '#template'       => '<a href="javascript:void(0);" class="settings_row tooltipster_init">{{ico|raw}}</a>
              <div class="tooltipster_content">
                  <a href="javascript:void(0);" class="button--link" onclick="{{link[0].onclick}}" data-post="{{link[0].post}}"><span class="ico">{{ link[0].ico|raw }}</span> {{ link[0].text }}</a><br/>
                  <a href="javascript:void(0);" class="button--link" onclick="{{link[1].onclick}}" data-post="{{link[1].post}}"><span class="ico">{{ link[1].ico|raw }}</span> {{ link[1].text }}</a>
              </div>',
            '#context'        => [
              'ico'             => $this->basket->getIco('settings_row.svg', 'base'),
              'link'            => [
              [
                'text'          => $this->basket->translate()->t('Edit'),
                'ico'           => $this->basket->getIco('edit.svg'),
                'onclick'       => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-edit_payment'])->toString() . '\')',
                'post'          => json_encode([
                  'type'          => $result->type,
                  'tid'           => $result->id,
                ]),
              ], [
                'text'          => $this->basket->translate()->t('Delete'),
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
    $form['payment_widget'] = [
      '#type'         => 'select',
      '#title'        => t('Widget'),
      '#options'      => [
        'select'        => t('Select list'),
        'radios'        => t('Radios'),
      ],
      '#default_value' => $this->basket->getSettings('enabled_services', 'payment_widget'),
      '#states'       => [
        'visible'       => [
          'input[name="payment_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['actions'] = [
      '#type'         => 'actions',
      'submit'        => [
        '#type'         => 'submit',
        '#value'        => $this->basket->translate()->t('Save'),
        '#ajax'         => [
          'wrapper'       => 'basket_payment_list_settings_form',
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
    // enabled_services.
    $this->basket->setSettings(
      'enabled_services',
      'payment',
      $form_state->getValue('payment_enabled')
    );
    $this->basket->setSettings(
      'enabled_services',
      'payment_widget',
      $form_state->getValue('payment_widget')
    );
    // Set active settings.
    $this->basket->setSettings(
      'active_services',
      'payment',
      $form_state->getValue('active_config')
    );
    // Set active default.
    $this->basket->setSettings(
      'active_services',
      'payment_default',
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
      \Drupal::service('Basket')->translate()->t('Settings saved.'),
    ]));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  private function getSettingsInfo($result) {
    if (!empty($this->servicesSettings[$result->id]) && !empty($system = $this->services[$this->servicesSettings[$result->id]])) {
      $items = \Drupal::service('BasketPayment')->getSettingsInfoList($result->id, $system);
      if (!empty($system['provider'])) {
        $module_info = \Drupal::service('extension.list.module')->getExtensionInfo($system['provider']);
        if (!empty($module_info['configure'])) {
          $items[] = [
            '#type'         => 'inline_template',
            '#template'     => '<a href="{{ url }}" target="_blank" class="button--link target">{{ text }}</a>',
            '#context'      => [
              'text'          => $this->basket->translate()->t('Settings page'),
              'url'           => Url::fromRoute($module_info['configure'])->toString(),
            ],
          ];
        }
      }
    }
    /*Connection with the delivery*/
    $deliveryItems = [];
    if (!empty($deliveryReference = $this->basket->getSettings('payment_delivery_reference', $result->id))) {
      $deliveryOptions = $this->basket->term()->getOptions('delivery');
      foreach ($deliveryReference as $tid) {
        if (empty($deliveryOptions[$tid])) {
          continue;
        }
        $deliveryItems[] = $deliveryOptions[$tid];
      }
    }
    if (empty($deliveryItems)) {
      $deliveryItems[] = $this->basket->translate()->t('For all deliveries');
    }
    if (!empty($deliveryItems)) {
      $items[] = [
        '#type'         => 'inline_template',
        '#template'     => '<b>{{ label }}:</b><br/>- {{ items|join(\'\n - \')|nl2br }}',
        '#context'      => [
          'label'         => $this->basket->translate()->t('Available on delivery'),
          'items'         => $deliveryItems,
        ],
      ];
    }
    /*Description*/
    if (!empty($result->description)) {
      $items[] = [
        '#type'         => 'inline_template',
        '#template'     => '<b>{{ label }}:</b><br/>- {{ description|nl2br }} {{ trans }}',
        '#context'      => [
          'label'         => $this->basket->translate()->t('Description'),
          'description'   => $this->basket->translate()->trans(trim($result->description)),
          'trans'         => $this->basket->translate()->getTranslateLink(trim($result->description)),
        ],
      ];
    }
    
    $notDiscounts = [];
    foreach (\Drupal::service('BasketDiscount')->getDefinitions() as $discount) {
      if(!empty($this->basket->getSettings('payment_not_discounts', $result->id.'.'.$discount['id']))) {
        $notDiscounts[$discount['id']] = $this->basket->translate($discount['provider'])->t(trim($discount['name']));
      }
    }
    $items[] = [
      '#type'         => 'inline_template',
      '#template'     => '<b>{{ label }}:</b><br/><ul>{% for item in items %}<li>{{ item }}</li>{% endfor %}</ul>',
      '#context'      => [
        'label'         => $this->basket->translate()->t('Block product discounts'),
        'items'         => !empty($notDiscounts) ? $notDiscounts : [t('no')]
      ],
    ];
    
    // Alter
    \Drupal::moduleHandler()->alter('payment_settings_info', $items, $result);
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

}
/**
 * {@inheritdoc}
 */
class PaymentSettingsForm extends FormBase {

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
   * @var array
   */
  protected $termServiceDef;

  /**
   * {@inheritdoc}
   */
  public function __construct($editTid = NULL) {
    $this->basket = \Drupal::service('Basket');
    $this->term = !empty($editTid) ? \Drupal::service('Basket')->term()->load($editTid) : NULL;
    if (!empty($this->term->id)) {
      $this->termServiceDef = $this->basket->getSettings('payment_services', $this->term->id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basket_payment_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form += [
      '#prefix'       => '<div id="basket_payment_settings_form_ajax_wrap">',
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
      '#title'        => $this->basket->translate()->trans('Name') . ' EN',
      '#required'     => TRUE,
      '#default_value' => !empty($this->term->name) ? $this->term->name : '',
    ];
    $form['description'] = [
      '#type'         => 'textarea',
      '#title'        => $this->basket->translate()->trans('Description') . ' EN',
      '#rows'         => 2,
      '#default_value' => !empty($this->term->description) ? $this->term->description : '',
    ];
    $services = [];
    if (!empty($payments = \Drupal::service('BasketPayment')->getDefinitions())) {
      foreach ($payments as $payment) {
        $services[$payment['id']] = $this->basket->translate(trim($payment['provider']))->trans(trim($payment['name']));
      }
    }
    $form['service'] = [
      '#type'         => 'select',
      '#title'        => $this->basket->translate()->t('Service'),
      '#options'      => $services,
      '#empty_option' => $this->basket->translate()->t('Not specified'),
      '#default_value' => $this->termServiceDef,
    ];
    $form['#submit'][] = [$this, 'submitFormSave'];
    /*Service alter*/
    \Drupal::service('BasketPayment')->paymentSettingsFormAlter($form, $form_state);
    /*Connection with the delivery*/
    $this->deliveryPaymentOptions($form, $form_state);
    /*Discounts*/
    $notDiscounts = [];
    foreach (\Drupal::service('BasketDiscount')->getDefinitions() as $discount) {
      $notDiscounts[$discount['id']] = $this->basket->translate($discount['provider'])->t(trim($discount['name']));
    }
    $form['not_discount'] = [
      '#type'         => 'checkboxes',
      '#title'        => $this->basket->translate()->t('Block product discounts'),
      '#options'      => $notDiscounts,
      '#default_value' => $this->basket->getSettings('payment_not_discounts', $form['tid']['#value']) ?? []
    ];
    /*---*/
    $form['actions'] = [
      '#type'         => 'actions',
      'submit'        => [
        '#type'         => 'submit',
        '#value'        => $this->basket->translate()->t('Save'),
        '#ajax'         => [
          'wrapper'       => 'basket_payment_settings_form_ajax_wrap',
          'callback'      => '::ajaxSubmit',
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state){}

  /**
   * {@inheritdoc}
   */
  public function submitFormSave(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!empty($values['tid'])) {
      \Drupal::database()->update('basket_terms')
        ->fields([
          'name'          => trim($values['name']),
          'description'   => trim($values['description']),
        ])
        ->condition('id', $values['tid'])
        ->execute();
    }
    else {
      $values['tid'] = \Drupal::database()->insert('basket_terms')
        ->fields([
          'type'          => 'payment',
          'name'          => trim($values['name']),
          'description'   => trim($values['description']),
          'weight'        => -100,
        ])
        ->execute();
    }
    /*Update service info*/
    $this->basket->setSettings(
      'payment_services',
      $values['tid'],
      $form_state->getValue('service')
    );
    $form_state->setValue('tid', $values['tid']);
    /*Connection with the delivery*/
    $deliverys = $form_state->getValue('delivery');
    if (!empty($deliverys)) {
      foreach ($deliverys as $key => $value) {
        if (empty($value)) {
          unset($deliverys[$key]);
        }
      }
    }
    $this->basket->setSettings(
      'payment_delivery_reference',
      $values['tid'],
      $deliverys
    );
    
    /*Discounts*/
    $this->basket->setSettings(
      'payment_not_discounts',
      $values['tid'],
      $form_state->getValue('not_discount')
    );
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

  /**
   * {@inheritdoc}
   */
  public function deliveryPaymentOptions(&$form, &$form_state) {
    $form['delivery'] = [
      '#type'         => 'checkboxes',
      '#title'        => $this->basket->translate()->t('Available on delivery'),
      '#options'      => \Drupal::service('Basket')->term()->getOptions('delivery'),
      '#description'  => $this->basket->translate()->t('If not specified, it will be available to anyone'),
      '#default_value' => !empty($this->term->id) ? $this->basket->getSettings('payment_delivery_reference', $this->term->id) : NULL,
    ];
  }

}
