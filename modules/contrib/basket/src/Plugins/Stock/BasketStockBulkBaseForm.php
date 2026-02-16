<?php

namespace Drupal\basket\Plugins\Stock;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;

/**
 * Basic form of quick buttons.
 */
abstract class BasketStockBulkBaseForm extends FormBase implements BasketStockBulkInterface {

  /**
   * Set params.
   *
   * @var array
   */
  protected static $params;

  /**
   * Set Basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set nodes.
   *
   * @var array
   */
  protected static $nodes;

  /**
   * {@inheritdoc}
   */
  public function __construct($params = []) {
    self::$params = $params;
    $this->basket = \Drupal::service('Basket');
    if (!empty(self::$params['nids'])) {
      self::$nodes = \Drupal::database()->select('node_field_data', 'n')
        ->fields('n', ['nid', 'title'])
        ->condition('n.nid', self::$params['nids'], 'in')
        ->condition('n.default_langcode', 1)
        ->execute()->fetchAllKeyed();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basket_stock_bulk_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form += [
      '#prefix'        => '<div id="basket_stock_bulk_form_ajax_wrap">',
      '#suffix'        => '</div>',
    ];
    $form['#title'] = $this->basket->Translate(self::$params['service']['provider'])->t('@string', ['@string' => self::$params['service']['name']]);
    $form['operationType'] = [
      '#type'            => 'hidden',
      '#value'        => 'bulk-' . self::$params['service']['id'],
    ];
    $form['operatinIds'] = [
      '#tree'                => TRUE,
    ];
    $rows = [];
    if (!empty(self::$nodes)) {
      foreach (self::$nodes as $nid => $title) {
        $rows[] = [
          $nid,
        [
          'data'        => [
            '#type'       => 'link',
            '#title'      => $title,
            '#url'        => new Url('entity.node.canonical', [
              'node'        => $nid,
            ], [
              'attributes' => [
                'target'    => '_blank',
              ],
            ]),
          ],
        ],
        ];
        $form['operatinIds'][] = [
          '#type'            => 'hidden',
          '#value'        => $nid,
        ];
      }
    }
    $form['params'] = [
      '#tree'         => TRUE,
      '#parents'      => [],
      '#access'       => !empty($rows),
    ];
    $this->getForm($form['params'], $form_state);
    $form['actions'] = [
      '#type'         => 'actions',
      '#access'       => !empty($rows),
      '#weight'       => 100,
      'submit'        => [
        '#type'         => 'submit',
        '#value'        => $this->basket->Translate()->t('To apply'),
        '#ajax'         => [
          'wrapper'       => 'basket_stock_bulk_form_ajax_wrap',
          'callback'      => '::ajaxSubmit',
          'progress'      => ['type' => 'fullscreen'],
        ],
        '#name'         => 'runBulk',
      ],
    ];
    $form['goods'] = [
      '#type'         => 'details',
      '#title'        => $this->basket->Translate()->t('Products'),
      '#open'         => empty($rows),
      '#weight'       => 101,
      'table'         => [
        '#theme'        => 'table',
        '#header'       => [
          'ID',
          $this->basket->Translate()->t('Product name'),
        ],
        '#rows'       => $rows,
        '#empty'      => $this->basket->Translate()->t('No product selected'),
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
  public function ajaxSubmit(array $form, FormStateInterface $form_state) {
    $triggerElement = $form_state->getTriggeringElement();
    if (!empty($triggerElement['#name']) && $triggerElement['#name'] == 'runBulk') {
      $operations = [];
      $transaction = \Drupal::database()->startTransaction();
      $settings = $this->getBulkSettings($form_state);
      foreach ($form_state->getValue('operatinIds') as $nid) {
        $operations[] = [[$this, 'formProcessBulk'], [[
          'nid'          => $nid,
          'settings'  => $settings,
        ],
        ],
        ];
      }
      $batch = [
        'title'             => $this->basket->Translate()->t('Apply changes'),
        'operations'        => $operations,
        'basket_batch'      => TRUE,
        'not_content'        => TRUE,
      ];
      batch_set($batch);
      unset($transaction);
      $response = batch_process(Url::fromRoute('basket.admin.pages', ['page_type' => 'stock-product']));
      $url = $response->getTargetUrl();
      $response = new AjaxResponse();
      return $response->addCommand(new RedirectCommand($url));
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function formProcessBulk($info) {
    $this->processBulk($info['nid'], $info['settings']);
  }

}
