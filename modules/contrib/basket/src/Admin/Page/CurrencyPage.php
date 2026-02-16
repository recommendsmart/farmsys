<?php

namespace Drupal\basket\Admin\Page;

use Drupal\basket\Admin\BasketDeleteConfirm;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Url;

/**
 * {@inheritdoc}
 */
class CurrencyPage {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set basket.
   *
   * @var object
   */
  protected $trans;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
    $this->trans = $this->basket->Translate();
  }

  /**
   * {@inheritdoc}
   */
  public function table() {
    return [
      '#prefix'       => '<div class="basket_table_wrap">',
      '#suffix'       => '</div>',
      'table'         => [
        '#prefix'       => '<div class="b_content">',
        '#suffix'       => '</div>',
        'form'          => \Drupal::formBuilder()->getForm(new CurrencyPageForm()),
      ],
      'CreateLink'    => [
        '#type'         => 'inline_template',
        '#template'     => '<a href="javascript:void(0);" onclick="{{onclick}}" data-post="{{post}}" id="CreateLink">+ {{text}}</a>',
        '#context'      => [
          'text'          => $this->trans->t('Create'),
          'onclick'       => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-create_currency'])->toString() . '\')',
          'post'          => json_encode([
            'cid'           => 'new',
          ]),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function apiResponseAlter(&$response) {
    if (isset($_POST['cid'])) {
      $currency = $this->basket->Currency()->load($_POST['cid']);
      \Drupal::service('BasketPopup')->openModal(
        $response,
        empty($currency) ? $this->trans->t('Create') : $this->trans->t('Edit'),
        \Drupal::formBuilder()->getForm(new CurrencyEditForm(), $currency),
        [
          'width' => 400,
          'class' => [],
        ]
      );
    }
    if (!empty($_POST['delete_cid'])) {
      $currency = $this->basket->Currency()->load($_POST['delete_cid']);
      if (!empty($currency)) {
        if (!empty($_POST['confirm'])) {
          $this->basket->Currency()->delete($currency->id);
          $response->addCommand(new InvokeCommand('body', 'append', ['<script>location.reload();</script>']));
        }
        else {
          \Drupal::service('BasketPopup')->openModal(
            $response,
            $this->trans->t('Delete') . ' "' . $currency->name . '"',
            BasketDeleteConfirm::confirmContent([
              'onclick'       => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-delete_currency'])->toString() . '\')',
              'post'          => json_encode([
                'delete_cid'    => $currency->id,
                'confirm'       => 1,
              ]),
            ]),
            [
              'width' => 400,
              'class' => [],
            ]
          );
        }
      }
    }
  }

}
/**
 * {@inheritdoc}
 */
class CurrencyPageForm extends FormBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set trans.
   *
   * @var object
   */
  protected $trans;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
    $this->trans = $this->basket->Translate();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basket_currency_page_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['status_messages'] = [
      '#type'         => 'status_messages',
    ];
    $form['all_pay'] = [
      '#type'             => 'radio',
      '#parents'          => ['pay_order'],
      '#return_value'     => 'all',
      '#title'            => $this->trans->t('Payment in site currency'),
      '#attributes'       => [
        'checked'           => 'all' == $this->basket->Currency()->getPayCurrency(TRUE),
      ],
    ];
    $form['config'] = [
      '#type'         => 'table',
      '#header'       => [
        '',
        $this->trans->t('Name'),
        $this->trans->t('Name prefix'),
        $this->trans->t('ISO'),
        $this->trans->t('Rate'),
        $this->trans->t('Default'),
        $this->trans->t('Currency to pay'),
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
      '#empty'        => $this->trans->t('The list is empty.'),
    ];
    if (!empty($results = $this->basket->Currency()->tree())) {
      foreach ($results as $result) {
        $form['config'][$result->id] = [
          '#attributes'       => [
            'class'             => ['draggable'],
          ],
          '#weight'           => $result->weight,
          'handle'            => [
            '#wrapper_attributes' => [
              'class'             => ['tabledrag-handle-td'],
            ],
          ],
          'id'                => [
            '#type'             => 'item',
            '#markup'           => $this->trans->trans(trim($result->name)),
            '#value'            => $result->id,
            '#field_suffix'     => $this->trans->getTranslateLink(trim($result->name)),
          ],
          'name_prefix'       => [
            '#type'             => 'item',
            '#markup'           => trim($result->name_prefix),
          ],
          'iso'               => [
            '#markup'           => $result->iso,
          ],
          'rate'              => [
            '#markup'           => round($result->rate, 5),
          ],
          'default'           => [
            '#type'             => 'radio',
            '#parents'          => ['default_new_order'],
            '#return_value'     => $result->id,
            '#title'            => 'on',
            '#attributes'       => [
              'class'             => ['not_label'],
              'checked'           => !empty($result->default),
            ],
            '#wrapper_attributes' => [
              'width'             => '20%;',
            ],
          ],
          'pay'               => [
            '#type'             => 'radio',
            '#parents'          => ['pay_order'],
            '#return_value'     => $result->id,
            '#title'            => 'on',
            '#attributes'       => [
              'class'             => ['not_label'],
              'checked'           => $result->id == $this->basket->Currency()->getPayCurrency(),
            ],
            '#wrapper_attributes' => [
              'width'             => '20%;',
            ],
          ],
          'weight'            => [
            '#type'             => 'number',
            '#attributes'       => [
              'class'             => ['group-order-weight'],
            ],
            '#default_value'    => $result->weight,
          ],
          'links'             => $this->getLinks($result),
        ];
      }
      $form['actions'] = [
        '#type'         => 'actions',
        'submit'        => [
          '#type'         => 'submit',
          '#value'        => $this->trans->t('Save'),
          '#ajax'         => [
            'wrapper'       => 'basket_term_page_settings_ajax_wrap',
            'callback'      => '::ajaxSubmit',
          ],
        ],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  private function getLinks($result) {
    $links = [
      [
        'text'          => $this->trans->t('Edit'),
        'ico'           => $this->basket->getIco('edit.svg'),
        'onclick'       => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-delete_currency'])->toString() . '\')',
        'post'          => json_encode([
          'cid'           => $result->id,
        ]),
      ],
    ];
    if (empty($result->locked)) {
      $links[] = [
        'text'          => $this->trans->t('Delete'),
        'ico'           => $this->basket->getIco('trash.svg'),
        'onclick'       => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-delete_currency'])->toString() . '\')',
        'post'          => json_encode([
          'delete_cid'    => $result->id,
        ]),
      ];
    }
    return [
      '#type'         => 'inline_template',
      '#template'     => '<a href="javascript:void(0);" class="settings_row tooltipster_init">{{ico|raw}}</a>
                            <div class="tooltipster_content">
                                <a href="javascript:void(0);" class="button--link" onclick="{{link[0].onclick}}" data-post="{{link[0].post}}"><span class="ico">{{ link[0].ico|raw }}</span> {{ link[0].text }}</a><br/>
                                {% if link[1] %}<a href="javascript:void(0);" class="button--link" onclick="{{link[1].onclick}}" data-post="{{link[1].post}}"><span class="ico">{{ link[1].ico|raw }}</span> {{ link[1].text }}</a>{% endif %}
                            </div>',
      '#context'      => [
        'ico'           => $this->basket->getIco('settings_row.svg', 'base'),
        'link'          => $links,
      ],
      '#wrapper_attributes' => [
        'class'             => ['td_settings_row'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('config') as $row) {
      \Drupal::database()->update('basket_currency')
        ->fields([
          'weight'    => $row['weight'],
        ])
        ->condition('id', $row['id'])
        ->execute();
    }
    if (!empty($form_state->getValue('default_new_order'))) {
      \Drupal::service('Basket')->Currency()->setDefault($form_state->getValue('default_new_order'));
    }
    /*Currency to pay*/
    \Drupal::service('Basket')->setSettings('currency_pay_order', 'cid', $form_state->getValue('pay_order'));
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand('.messages, .tabledrag-changed', 'remove', []));
    $response->addCommand(new InvokeCommand('.drag-previous', 'removeClass', ['drag-previous']));
    $response->addCommand(new InvokeCommand(NULL, 'NotyGenerate', [
      'status',
      $this->trans->t('Settings saved.'),
    ]));
    return $response;
  }

}
/**
 * {@inheritdoc}
 */
class CurrencyEditForm extends FormBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set trans.
   *
   * @var object
   */
  protected $trans;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
    $this->trans = $this->basket->Translate();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basket_currency_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $currency = NULL) {
    $form['#prefix'] = '<div id="basket_currency_edit_form_ajax_wrap">';
    $form['#suffix'] = '</div>';
    $form['status_messages'] = [
      '#type'         => 'status_messages',
    ];
    $form['cid'] = [
      '#type'         => 'hidden',
      '#value'        => !empty($currency->id) ? $currency->id : NULL,
    ];
    $form['name'] = [
      '#type'         => 'textfield',
      '#required'     => TRUE,
      '#title'        => $this->trans->trans('Name') . ' EN',
      '#default_value' => !empty($currency->name) ? trim($currency->name) : '',
    ];
    $form['prefix'] = [
      '#type'         => 'textfield',
      '#required'     => TRUE,
      '#title'        => $this->trans->t('Name prefix'),
      '#default_value' => !empty($currency->name_prefix) ? trim($currency->name_prefix) : '',
    ];
    $form['iso'] = [
      '#type'         => 'textfield',
      '#required'     => TRUE,
      '#title'        => $this->trans->t('ISO'),
      '#default_value' => !empty($currency->iso) ? trim($currency->iso) : '',
    ];
    $form['rate'] = [
      '#type'         => 'number',
      '#required'     => TRUE,
      '#title'        => $this->trans->t('Rate'),
      '#min'          => 0,
      '#step'         => 0.00001,
      '#default_value' => !empty($currency->rate) ? trim($currency->rate) : '',
    ];
    $form['actions'] = [
      '#type'         => 'actions',
      'submit'        => [
        '#type'         => 'submit',
        '#value'        => $this->trans->t('Save'),
        '#ajax'         => [
          'wrapper'       => 'basket_currency_edit_form_ajax_wrap',
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
    // Clear cache.
    $this->basket->Currency()->clearCache();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!empty($iso = $form_state->getValue('iso'))) {
      $currency = $this->basket->Currency()->loadByISO(trim($iso));
      if (!empty($currency) && $currency->id !== $form_state->getValue('cid')) {
        $form_state->setError($form['iso'], $this->trans->t('ISO must be unique!'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    if ($form_state->isSubmitted() && $form_state->getErrors()) {
      return $form;
    }
    else {
      $values = $form_state->getValues();
      if (!empty($values['cid'])) {
        \Drupal::database()->update('basket_currency')
          ->fields([
            'name'          => trim($values['name']),
            'name_prefix'   => !empty($values['prefix']) ? trim($values['prefix']) : '',
            'iso'           => trim($values['iso']),
            'rate'          => trim($values['rate']),
          ])
          ->condition('id', $values['cid'])
          ->execute();
      }
      else {
        \Drupal::database()->insert('basket_currency')
          ->fields([
            'name'          => trim($values['name']),
            'name_prefix'   => !empty($values['prefix']) ? trim($values['prefix']) : '',
            'iso'           => trim($values['iso']),
            'rate'          => trim($values['rate']),
            'weight'        => 100,
          ])
          ->execute();
      }
      $response = new AjaxResponse();
      $response->addCommand(new InvokeCommand('body', 'append', ['<script>location.reload();</script>']));
      return $response;
    }
  }

}
