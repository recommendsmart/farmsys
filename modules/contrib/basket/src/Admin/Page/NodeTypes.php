<?php

namespace Drupal\basket\Admin\Page;

use Drupal\basket\Admin\BasketDeleteConfirm;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * {@inheritdoc}
 */
class NodeTypes {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set trans.
   *
   * @var Drupal\basket\BasketTranslate
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
        [
          '#theme'        => 'table',
          '#header'       => [
            $this->trans->t('Material type'),
            $this->trans->t('Field pictures'),
            $this->trans->t('Field price'),
            $this->trans->t('Field count'),
            $this->trans->t('Extra fields'),
            '',
          ],
          '#rows'         => $this->getRows(),
          '#empty'        => $this->trans->t('The list is empty.'),
        ],
      ],
      'CreateLink'        => [
        '#type'         => 'inline_template',
        '#template'     => '<a href="javascript:void(0);" onclick="{{onclick}}" data-post="{{post}}" id="CreateLink">+ {{text}}</a>',
        '#context'      => [
          'text'          => $this->trans->t('Create'),
          'onclick'       => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-add_node_type'])->toString() . '\')',
          'post'          => json_encode([]),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  private function getRows() {
    $rows = [];
    // ---
    $results = \Drupal::database()->select('basket_node_types', 'n')
      ->fields('n')
      ->execute()->fetchAll();
    if (!empty($results)) {
      $nodeTypes = \Drupal::service('entity_type.manager')->getStorage('node_type')->loadMultiple();
      foreach ($results as $result) {
        if (empty($nodeTypes[$result->type])) {
          continue;
        }

        $rows[] = [
          [
            'data'      => [
              '#markup'       => '<b>' . $nodeTypes[$result->type]->label() . '</b> (' . $result->type . ')',
            ],
          ],
          $this->getFieldName($result->type, [$result->image_field], $this->basket->BASKET_FIELD_IMAGES),
          $this->getFieldName($result->type, [$result->price_field], $this->basket->BASKET_FIELD_PRICES),
          $this->getFieldName($result->type, [$result->count_field], $this->basket->BASKET_FIELD_COUNT),
          $this->getExtraFields($result->extra_fields),
          [
            'data'      => [
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
                    'text'          => $this->trans->t('Edit'),
                    'ico'           => $this->basket->getIco('edit.svg'),
                    'onclick'       => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-add_node_type'])->toString() . '\')',
                    'post'          => json_encode([
                      'node_type'     => $result->type,
                    ]),
                  ], [
                    'text'          => $this->trans->t('Delete'),
                    'ico'           => $this->basket->getIco('trash.svg'),
                    'onclick'       => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-delete_node_type'])->toString() . '\')',
                    'post'          => json_encode([
                      'delete_node_type' => $result->type,
                    ]),
                  ],
                ],
              ],
            ],
            'class'         => ['td_settings_row'],
          ],
        ];
      }
    }
    // ---
    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  private function getFieldName($nodeType, $fieldNames, $fieldsTypes = []) {
    $items = [];
    foreach ($fieldNames as $fieldName) {
      $getFields = $this->basket->getNodeTypeFields($nodeType, $fieldsTypes);
      if (!empty($getFields[$fieldName])) {
        $items[] = str_replace('[', '<br/>[', $getFields[$fieldName]);
      }
    }
    if (!empty($items)) {
      return [
        'data'      => [
          '#markup'   => implode('<br/><br/>', $items),
        ],
        'title'     => $this->trans->t('Field can be overridden'),
      ];
    }
    return [
      'data'      => $this->trans->t('Not specified'),
      'class'     => ['not_specified'],
      'title'     => $this->trans->t('Field can be overridden'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  private function getExtraFields($extra_fields) {
    $fields = [];
    if (!empty($extra_fields)) {
      foreach (unserialize($extra_fields) as $key => $value) {
        if (empty($value['on'])) {
          continue;
        }
        switch ($key) {
          case'add':
            $fields[$key] = [
              '#type'         => 'inline_template',
              '#template'     => '<b>{{label|raw}}</b>{% if alls %}
                            <ul>
                                {% for all in alls %}
                                    <li>{{ all|raw }}</li>
                                {% endfor %}
                            </ul>
                            {% endif %}',
              '#context'      => [
                'label'         => $this->trans->t('Add button'),
                'alls'          => [],
              ],
            ];
            if (!empty($value['text'])) {
              $translate = $this->trans->getTranslateLink(trim($value['text']));
              $fields[$key]['#context']['alls'][] = $this->trans->trans('Button text')
                                                                    . ': '
                                                                    . $value['text']
                                                                    . ' '
                                                                    . \Drupal::service('renderer')->render($translate);
            }
            if (!empty($value['count'])) {
              $fields[$key]['#context']['alls'][] = $this->trans->t('Show + / -');
            }
            break;

          case'add_params':
            $fields[$key] = [
              '#markup'       => '<b>' . $this->trans->t('Selection of parameters') . '</b>',
            ];
            break;

          case'add_count_sum':
            $fields[$key] = [
              '#markup'       => '<b>' . $this->trans->t('Summarize products when adding to cart') . '</b>',
            ];
            break;

          default:
            $fields[$key] = [];
            /*Alter*/
            \Drupal::moduleHandler()->alter('basket_node_type_extra_fields_list', $fields[$key], $value, $key);
            /*END Alter*/
            break;
        }
      }
    }
    return [
      'data'      => $fields,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function apiResponseAlter(&$response) {
    if (!empty($_POST['delete_node_type'])) {
      if (!empty($_POST['confirm'])) {
        \Drupal::database()->delete('basket_node_types')
          ->condition('type', $_POST['delete_node_type'])
          ->execute();
        $response->addCommand(new InvokeCommand('body', 'append', ['<script>location.reload();</script>']));
      }
      else {
        \Drupal::service('BasketPopup')->openModal(
          $response,
          $this->trans->t('Delete') . ' "' . $_POST['delete_node_type'] . '"',
          BasketDeleteConfirm::confirmContent([
            'onclick'       => 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-delete_node_type'])->toString() . '\')',
            'post'          => json_encode([
              'delete_node_type'    => $_POST['delete_node_type'],
              'confirm'             => 1,
            ]),
          ]),
          [
            'width' => 400,
            'class' => ['basket_add_popup'],
          ]
        );
      }
    }
    else {
      \Drupal::service('BasketPopup')->openModal(
        $response,
        empty($_POST['node_type']) ? $this->trans->t('Create') : $this->trans->t('Edit'),
        \Drupal::formBuilder()->getForm(
            new ExtraFieldsNodeTypeForm(),
            !empty($_POST['node_type']) ? $_POST['node_type'] : NULL
        ), [
          'width' => 900,
          'class' => ['basket_add_popup'],
        ]
      );
    }
  }

}
/**
 * {@inheritdoc}
 */
class ExtraFieldsNodeTypeForm extends FormBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set trans.
   *
   * @var Drupal\basket\BasketTranslate
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
    return 'basket_node_type_fields_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $nodeTypeActive = NULL) {
    $form['#prefix'] = '<div id="basket_extra_fields_node_type_edit_form_ajax_wrap">';
    $form['#suffix'] = '</div>';
    // ---
    $values = $form_state->getValues();
    if (empty($values) && !empty($nodeTypeActive)) {
      $values['node_type'] = $nodeTypeActive;
      $nodeTypeActive = \Drupal::database()->select('basket_node_types', 'n')
        ->fields('n')
        ->condition('n.type', $nodeTypeActive)
        ->execute()->fetchObject();
      if (!empty($nodeTypeActive->extra_fields)) {
        $nodeTypeActive->extra_fields = unserialize($nodeTypeActive->extra_fields);
      }
    }
    // ---
    $ajaxReload = [
      'wrapper'       => 'basket_extra_fields_node_type_edit_form_ajax_wrap',
      'callback'      => '::ajaxReload',
    ];
    $form['status_messages'] = [
      '#type'         => 'status_messages',
    ];
    $form['node_type'] = [
      '#type'         => 'select',
      '#title'        => $this->trans->t('Material type'),
      '#required'     => TRUE,
      '#options'      => $this->getNodeTypesList(),
      '#ajax'         => $ajaxReload,
      '#default_value' => !empty($nodeTypeActive->type) ? $nodeTypeActive->type : NULL,
    ];
    if (!empty($values['node_type'])) {
      $form['image_field'] = [
        '#type'         => 'select',
        '#title'        => $this->trans->t('Field pictures'),
        '#options'      => $this->basket->getNodeTypeFields($values['node_type'], $this->basket->BASKET_FIELD_IMAGES),
        '#empty_option' => t('- Select -'),
        '#default_value' => !empty($nodeTypeActive->image_field) ? $nodeTypeActive->image_field : NULL,
      ];
      $form['price_field'] = [
        '#type'         => 'select',
        '#title'        => $this->trans->t('Field price'),
        '#options'      => $this->basket->getNodeTypeFields($values['node_type'], $this->basket->BASKET_FIELD_PRICES),
        '#empty_option' => t('- Select -'),
        '#default_value' => !empty($nodeTypeActive->price_field) ? $nodeTypeActive->price_field : NULL,
      ];
      $form['count_field'] = [
        '#type'         => 'select',
        '#title'        => $this->trans->t('Field count'),
        '#options'      => $this->basket->getNodeTypeFields($values['node_type'], $this->basket->BASKET_FIELD_COUNT),
        '#empty_option' => t('- Select -'),
        '#default_value' => !empty($nodeTypeActive->count_field) ? $nodeTypeActive->count_field : NULL,
      ];
      $form['extra_fields'] = [
        '#type'         => 'details',
        '#open'         => TRUE,
        '#title'        => $this->trans->t('Extra fields'),
        'table'         => [
          '#type'             => 'table',
          '#NodeTypeActive'   => $nodeTypeActive,
          [
            [
              '#type'         => 'checkbox',
              '#title'        => $this->trans->t('Add button'),
              '#parents'      => ['extra_fields', 'add', 'on'],
              '#default_value' => !empty($nodeTypeActive->extra_fields['add']['on']) ? 1 : 0,
            ], [
              '#type'         => 'textfield',
              '#attributes'   => [
                'placeholder'   => $this->trans->t('Button text'),
              ],
              '#parents'      => ['extra_fields', 'add', 'text'],
              '#states'       => [
                'visible'       => [
                  'input[name="extra_fields[add][on]"]' => ['checked' => TRUE],
                ],
              ],
              '#default_value' => !empty($nodeTypeActive->extra_fields['add']['text']) ? $nodeTypeActive->extra_fields['add']['text'] : '',
            ], [
              '#type'         => 'checkbox',
              '#title'        => $this->trans->t('Show + / -'),
              '#parents'      => ['extra_fields', 'add', 'count'],
              '#states'       => [
                'visible'       => [
                  'input[name="extra_fields[add][on]"]' => ['checked' => TRUE],
                ],
              ],
              '#default_value' => !empty($nodeTypeActive->extra_fields['add']['count']) ? 1 : 0,
              '#wrapper_attributes' => ['colspan' => 2],
            ],
          ], [
            [
              '#type'         => 'checkbox',
              '#title'        => $this->trans->t('Summarize products when adding to cart'),
              '#parents'      => ['extra_fields', 'add_count_sum', 'on'],
              '#default_value' => !empty($nodeTypeActive->extra_fields['add_count_sum']['on']) ? 1 : 0,
              '#wrapper_attributes' => ['colspan' => 5],
            ],
          ], [
            [
              '#type'         => 'checkbox',
              '#title'        => $this->trans->t('Selection of parameters'),
              '#parents'      => ['extra_fields', 'add_params', 'on'],
              '#default_value' => !empty($nodeTypeActive->extra_fields['add_params']['on']) ? 1 : 0,
              '#wrapper_attributes' => ['colspan' => 5],
            ],
          ],
        ],
      ];
      /*Alter*/
      \Drupal::moduleHandler()->alter('basket_node_type_extra_fields_form', $form['extra_fields']['table'], $form_state);
      /*END alter*/
    }
    $form['actions'] = [
      '#type'         => 'actions',
      'submit'        => [
        '#type'         => 'submit',
        '#value'        => $this->trans->t('Save'),
        '#ajax'         => $ajaxReload,
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function ajaxReload(array &$form, FormStateInterface $form_state) {
    if ($form_state->isSubmitted() && !$form_state->getErrors()) {
      $values = $form_state->getValues();
      \Drupal::database()->merge('basket_node_types')
        ->key([
          'type'              => $values['node_type'],
        ])
        ->fields([
          'type'              => $values['node_type'],
          'image_field'       => !empty($values['image_field']) ? $values['image_field'] : NULL,
          'price_field'       => !empty($values['price_field']) ? $values['price_field'] : NULL,
          'count_field'       => !empty($values['count_field']) ? $values['count_field'] : NULL,
          'extra_fields'      => !empty($values['extra_fields']) ? serialize($values['extra_fields']) : NULL,
        ])
        ->execute();
      drupal_flush_all_caches();
      $response = new AjaxResponse();
      $response->addCommand(new InvokeCommand('body', 'append', ['<script>location.reload();</script>']));
      return $response;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeTypesList() {
    $nodeTypes = \Drupal::service('entity_type.manager')->getStorage('node_type')->loadMultiple();
    $options = [];
    foreach ($nodeTypes as $nodeType) {
      if ($nodeType->id() == 'basket_order') {
        continue;
      }
      $options[$nodeType->id()] = $nodeType->label();
    }
    return $options;
  }

}
