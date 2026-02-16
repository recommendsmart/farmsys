<?php

namespace Drupal\basket\Admin;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\basket\Plugin\views\field\BasketProductStatusField;

/**
 * {@inheritdoc}
 */
class Operations {

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
  public function apiResponseAlter(&$response, $page_subtype) {
    if (!empty($_POST['operationType'])) {
      $operationSubType = NULL;
      @list($_POST['operationType'], $operationSubType) = @explode('-', $_POST['operationType']);
      switch ($_POST['operationType']) {
        case'deleteNodes':
          $nids = !empty($_POST['operatinIds']) ? $_POST['operatinIds'] : [];
          \Drupal::service('BasketPopup')->openModal(
            $response,
            $this->basket->Translate()->t('Delete'),
            \Drupal::formBuilder()->getForm(new BasketNodeDeleteMultiple($nids)),
            [
              'width' => 400,
              'class' => ['basket_node_delete_multiple'],
            ]
              );
          break;

        case'stopNodes':
          if (!empty($_POST['operatinIds'])) {
            foreach (\Drupal::service('entity_type.manager')->getStorage('node')->loadMultiple($_POST['operatinIds']) as $node) {
              $node->set('status', 0);
              $node->save();
              $response->addCommand(new ReplaceCommand('.basket_product_status_' . $node->id(), BasketProductStatusField::statusHtml($node->id(), 0, $this->basket)));
              \Drupal::service('entity_type.manager')->getViewBuilder('node')->resetCache([$node]);
            }
            $response->addCommand(new ReplaceCommand('.goods_count_info', [
              '#theme'            => 'basket_area_buttons_goods_info',
              '#info'             => [
                'goodsInfo'         => $this->basket->getCounts('goodsInfo'),
              ],
            ]));
          }
          break;

        case'playNodes':
          if (!empty($_POST['operatinIds'])) {
            foreach (\Drupal::service('entity_type.manager')->getStorage('node')->loadMultiple($_POST['operatinIds']) as $node) {
              $node->set('status', 1);
              $node->save();
              $response->addCommand(new ReplaceCommand('.basket_product_status_' . $node->id(), BasketProductStatusField::statusHtml($node->id(), 1, $this->basket)));
              \Drupal::service('entity_type.manager')->getViewBuilder('node')->resetCache([$node]);
            }
            $response->addCommand(new ReplaceCommand('.goods_count_info', [
              '#theme'            => 'basket_area_buttons_goods_info',
              '#info'             => [
                'goodsInfo'         => $this->basket->getCounts('goodsInfo'),
              ],
            ]));
          }
          break;

        case'bulk':
          if (!empty($operationSubType)) {
            $nids = !empty($_POST['operatinIds']) ? $_POST['operatinIds'] : [];
            $form = \Drupal::service('BasketStockBulk')->getForm($operationSubType, $nids);
            if (!empty($form)) {
              \Drupal::service('BasketPopup')->openModal(
                $response,
                !empty($form['#title']) ? $form['#title'] : '',
                $form,
                [
                  'width' => 960,
                  'class' => ['basket_bulk_multiple'],
                ]
              );
            }
          }
          break;
      }
    }
  }

}
/**
 * {@inheritdoc}
 */
class BasketNodeDeleteMultiple extends FormBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set nodes.
   *
   * @var array
   */
  protected $nodes;

  /**
   * {@inheritdoc}
   */
  public function __construct($nids = []) {
    $nids[] = 0;
    $this->basket = \Drupal::service('Basket');
    $this->nodes = \Drupal::database()->select('node_field_data', 'n')
      ->fields('n', ['nid', 'title'])
      ->condition('n.nid', $nids, 'in')
      ->condition('n.default_langcode', 1)
      ->execute()->fetchAllKeyed();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basket_node_delete_multiple_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form += [
      '#prefix'       => '<div id="basket_node_delete_multiple_form_ajax_wrap" class="basket_table_wrap">',
      '#suffix'       => '</div>',
    ];
    $form['operationType'] = [
      '#type'         => 'hidden',
      '#value'        => $_POST['operationType'],
    ];
    $form['operatinIds'] = [
      '#tree'            => TRUE,
    ];
    $rows = [];
    if (!empty($this->nodes)) {
      foreach ($this->nodes as $nid => $title) {
        $rows[] = [
          $nid,
        [
          'data'        => [
            '#type'       => 'link',
            '#title'      => $title,
            '#url'        => new Url('entity.node.canonical', [
              'node'        => $nid,
            ], [
              'attributes'  => [
                'target'      => '_blank',
              ],
            ]),
          ],
        ],
        ];
        $form['operatinIds'][] = [
          '#type'       => 'hidden',
          '#value'      => $nid,
        ];
      }
    }
    $form['table'] = [
      '#theme'      => 'table',
      '#header'     => [
        'ID',
        $this->basket->Translate()->t('Product name'),
      ],
      '#rows'       => $rows,
      '#empty'      => $this->basket->Translate()->t('No product selected'),
    ];
    if (!empty($rows)) {
      $form['actions'] = [
        '#type'         => 'actions',
        'submit'        => [
          '#type'         => 'submit',
          '#value'        => $this->basket->Translate()->t('Delete'),
          '#ajax'         => [
            'wrapper'       => 'basket_node_delete_multiple_form_ajax_wrap',
            'callback'      => '::ajaxSubmitDelete',
          ],
        ],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state){}

  /**
   * {@inheritdoc}
   */
  public function ajaxSubmitDelete(array $form, FormStateInterface $form_state) {
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($form_state->getValue('operatinIds'));
    if (!empty($nodes)) {
      foreach ($nodes as $node) {
        \Drupal::database()->merge('basket_node_delete')
          ->key([
            'nid'           => $node->id(),
          ])
          ->fields([
            'uid'           => \Drupal::currentUser()->id(),
            'delete_time'   => time(),
          ])
          ->execute();
        $node->set('status', 0);
        $node->save();
      }
    }
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand('.views-exposed-form .form-submit', 'trigger', ['click']));
    $response->addCommand(new InvokeCommand('body', 'append', ['<script>' . \Drupal::service('BasketPopup')->getCloseOnclick() . '</script>']));
    return $response;
  }

}
