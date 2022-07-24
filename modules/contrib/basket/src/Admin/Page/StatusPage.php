<?php

namespace Drupal\basket\Admin\Page;

use Drupal\basket\Admin\BasketDeleteConfirm;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;

/**
 * {@inheritdoc}
 */
class StatusPage {

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
  public function table($page_type = NULL) {
    return [
      '#attached'     => [
        'library'       => [
          'basket/colorpicker',
        ],
      ],
      '#prefix'       => '<div class="basket_table_wrap">',
      '#suffix'       => '</div>',
      [
        '#prefix'       => '<div class="b_content">',
        '#suffix'       => '</div>',
          [
            \Drupal::formBuilder()->getForm(new TermPageSettingsForm($page_type)),
          ],
      ],
      'CreateLink'    => [
        '#type'         => 'inline_template',
        '#template'     => '<a href="javascript:void(0);" onclick="{{onclick}}" data-post="{{post}}" id="CreateLink">+ {{text}}</a>',
        '#context'      => [
          'text'          => $this->trans->t('Create'),
          'onclick'       => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-create_term'])->toString() . '\')',
          'post'          => json_encode([
            'type'          => $page_type,
          ]),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function apiResponseAlter(&$response) {
    // create_term / edit_term.
    if (!empty($_POST['type'])) {
      \Drupal::service('BasketPopup')->openModal(
         $response,
         empty($_POST['tid']) ? $this->trans->t('Create') : $this->trans->t('Edit'),
         \Drupal::formBuilder()->getForm(
          '\Drupal\basket\Admin\Form\TermSettingsForm',
          $_POST['type'],
          !empty($_POST['tid']) ? $_POST['tid'] : NULL
         ), [
           'width' => 400,
           'class' => ['basket_add_popup'],
         ]
      );
    }
    // delete_term.
    if (!empty($_POST['delete_tid']) && !empty($term = $this->basket->Term()->load($_POST['delete_tid']))) {
      if (!empty($_POST['confirm'])) {
        $this->basket->Term()->delete($term->id);
        $response->addCommand(new InvokeCommand('body', 'append', ['<script>location.reload();</script>']));
      }
      else {
        \Drupal::service('BasketPopup')->openModal(
          $response,
          $this->trans->t('Delete') . ' "' . $term->name . '"',
          BasketDeleteConfirm::confirmContent([
            'onclick'       => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-delete_term'])->toString() . '\')',
            'post'          => json_encode([
              'delete_tid'    => $term->id,
              'confirm'       => 1,
              'type'          => $term->type,
            ]),
          ]),
          [
            'width' => 400,
            'class' => ['basket_add_popup'],
          ]
        );
      }
    }
  }

}
/**
 * {@inheritdoc}
 */
class TermPageSettingsForm extends FormBase {

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
   * Set pageType.
   *
   * @var string
   */
  protected $pageType;

  /**
   * {@inheritdoc}
   */
  public function __construct($pageType = NULL) {
    $this->basket = \Drupal::service('Basket');
    $this->trans = $this->basket->Translate();
    $this->pageType = $pageType;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basket_term_page_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="basket_term_page_settings_ajax_wrap">';
    $form['#suffix'] = '</div>';
    $form['status_messages'] = [
      '#type'            => 'status_messages',
    ];
    $form['config'] = [
      '#type'         => 'table',
      '#header'       => [
        '',
        $this->trans->t('Name'),
        $this->trans->t('Color'),
        $this->trans->t('By default when creating an order'),
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
    if (!empty($results = $this->basket->Term()->tree($this->pageType))) {
      foreach ($results as $result) {
        $form['config'][$result->id] = [
          '#attributes'     => [
            'class'           => ['draggable'],
          ],
          '#weight'         => $result->weight,
          'handle'          => [
            '#wrapper_attributes' => [
              'class'         => ['tabledrag-handle-td'],
            ],
          ],
          'id'              => [
            '#type'           => 'item',
            '#markup'         => $this->trans->trans(trim($result->name)),
            '#value'          => $result->id,
            '#field_suffix'   => $this->trans->getTranslateLink(trim($result->name)),
          ],
          'color'           => [
            '#markup'         => Markup::create('<div class="color_input" style="background:' . $result->color . '"></div>'),
          ],
          'default'         => [
            '#type'           => 'radio',
            '#parents'        => ['default_new_order'],
            '#return_value'   => $result->id,
            '#title'          => 'on',
            '#attributes'     => [
              'class'           => ['not_label'],
              'checked'         => !empty($result->default),
            ],
            '#wrapper_attributes' => [
              'width'           => '20%;',
            ],
          ],
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
                  'text'          => $this->basket->Translate()->t('Edit'),
                  'ico'           => $this->basket->getIco('edit.svg'),
                  'onclick'       => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-edit_term'])->toString() . '\')',
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
                    'type'          => $result->type,
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('config') as $row) {
      \Drupal::database()->update('basket_terms')
        ->fields([
          'weight'    => $row['weight'],
        ])
        ->condition('id', $row['id'])
        ->execute();
    }
    if (!empty($form_state->getValue('default_new_order'))) {
      $this->basket->Term()->setDefaultNewOrder($form_state->getValue('default_new_order'));
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
