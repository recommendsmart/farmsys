<?php

namespace Drupal\basket\Admin\Page;

use Drupal\views\Views;
use Drupal\Core\Url;

/**
 * {@inheritdoc}
 */
class Trash {

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
   * Page.
   */
  public function page() {
    return [
      'orders'        => [
        '#prefix'       => '<div class="basket_table_wrap trash_page">',
        '#suffix'       => '</div>',
        'title'         => [
          '#prefix'       => '<div class="b_title">',
          '#suffix'       => '</div>',
          '#markup'       => $this->basket->Translate()->t('Trash can') . ' "' . $this->basket->Translate()->t('Orders') . '"',
        ],
        'content'       => [
          '#prefix'       => '<div class="b_content">',
          '#suffix'       => '</div>',
          'view'          => $this->basket->getView('basket', 'block_1', 'is_delete'),
        ],
      ],
      'nodes'         => [
        '#prefix'       => '<div class="basket_table_wrap trash_page">',
        '#suffix'       => '</div>',
        'title'         => [
          '#prefix'       => '<div class="b_title">',
          '#suffix'       => '</div>',
          '#markup'       => $this->basket->Translate()->t('Trash can') . ' "' . $this->basket->Translate()->t('Product') . '"',
        ],
        'content'       => [
          '#prefix'       => '<div class="b_content">',
          '#suffix'       => '</div>',
          'view'          => $this->basket->getView('basket', 'block_3', 'is_delete'),
        ],
      ],
    ];
  }

  /**
   * GetCaptionItems.
   */
  public function getCaptionItems($type = 'orders') {
    $items = [];
    if (\Drupal::currentUser()->hasPermission('basket access_restore_' . $type)) {
      $items[] = [
        'name'      => $this->basket->Translate()->trans('Restore all ' . $type),
        'url'       => Url::fromRoute('basket.admin.pages', ['page_type' => 'trash-restore-' . $type])->toString(),
      ];
    }
    if (\Drupal::currentUser()->hasPermission('basket access_trash_clear_page')) {
      $items[] = [
        'name'      => $this->basket->Translate()->trans('Delete all ' . $type),
        'url'       => Url::fromRoute('basket.admin.pages', ['page_type' => 'trash-delete-' . $type])->toString(),
      ];
    }
    return $items;
  }

  /**
   * RestoreBath.
   */
  public function restoreBath($type) {
    if (!\Drupal::currentUser()->hasPermission('basket access_restore_order')) {
      return \Drupal::service('Basket')->getError(403);
    }
    $bathTitle = NULL;
    switch ($type) {
      case'orders':
        if (!empty(Views::getEnabledViews()['basket'])) {
          $view = Views::getView('basket');
          $view->setItemsPerPage(0);
          $view->executeDisplay('block_1', ['is_delete']);
          if (!empty($view->result)) {
            foreach ($view->result as $row) {
              if (empty($row->basket_orders_id)) {
                continue;
              }
              $operations[] = [
                __CLASS__ . '::restoreOrderProcess',
                [$row->basket_orders_id],
              ];
            }
          }
        }
        $bathTitle = \Drupal::service('Basket')->Translate()->t('Recover deleted orders');
        break;

      case'products':
        $operations[] = ['\\' . __CLASS__ . '::restoreProductsProcess', []];
        $bathTitle = \Drupal::service('Basket')->Translate()->t('Recover deleted products');
        break;
    }
    if (!empty($operations)) {
      $batch = [
        'title'             => $bathTitle,
        'operations'        => $operations,
        'basket_batch'      => TRUE,
      ];
      batch_set($batch);
      $response = batch_process(Url::fromRoute('basket.admin.pages', ['page_type' => 'trash'])->toString());
      $response->send();
    }
    else {
      return \Drupal::service('Basket')->getError(404);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function restoreOrderProcess($orderId, &$context) {
    $order = \Drupal::service('Basket')->Orders($orderId);
    $order->set('is_delete', NULL);
    $order->save();
  }

  /**
   * {@inheritdoc}
   */
  public static function restoreProductsProcess() {
    \Drupal::database()->truncate('basket_node_delete')->execute();
  }

  /**
   * DeleteBath.
   */
  public function deleteBath($type) {
    if (!\Drupal::currentUser()->hasPermission('basket access_trash_clear_page')) {
      return \Drupal::service('Basket')->getError(403);
    }
    $bathTitle = NULL;
    switch ($type) {
      case'orders':
        if (!empty(Views::getEnabledViews()['basket'])) {
          $view = Views::getView('basket');
          $view->setItemsPerPage(0);
          $view->executeDisplay('block_1', ['is_delete']);
          if (!empty($view->result)) {
            foreach ($view->result as $row) {
              if (empty($row->basket_orders_id)) {
                continue;
              }
              $operations[] = [
                __CLASS__ . '::deleteOrderProcess',
                [$row->basket_orders_id],
              ];
            }
          }
        }
        $bathTitle = \Drupal::service('Basket')->Translate()->t('Irreversible order removal');
        if (!empty($operations)) {
          return [
            '#prefix'       => '<div class="basket_table_wrap">',
            '#suffix'       => '</div>',
          [
            '#prefix'       => '<div class="b_title">',
            '#suffix'       => '</div>',
            '#markup'       => $this->basket->Translate()->t('Confirm removal'),
          ], [
            '#prefix'       => '<div class="b_content">',
            '#suffix'       => '</div>',
            'form'          => \Drupal::formBuilder()->getForm(new TrashConfirmDelete($operations, $bathTitle)),
          ],
          ];
        }
        else {
          return \Drupal::service('Basket')->getError(404);
        }
        break;

      case'products':
        $bathTitle = \Drupal::service('Basket')->Translate()->t('Irreversible products removal');
        // ---
        $nids = \Drupal::database()->select('basket_node_delete', 'n')
          ->fields('n', ['nid'])
          ->execute()->fetchCol();
        if (!empty($nids)) {
          foreach ($nids as $nid) {
            $operations[] = [
              __CLASS__ . '::deleteProductProcess',
              [$nid],
            ];
          }
        }
        // ---
        if (!empty($operations)) {
          return [
            '#prefix'       => '<div class="basket_table_wrap">',
            '#suffix'       => '</div>',
            [
              '#prefix'       => '<div class="b_title">',
              '#suffix'       => '</div>',
              '#markup'       => $this->basket->Translate()->t('Confirm removal'),
            ], [
              '#prefix'       => '<div class="b_content">',
              '#suffix'       => '</div>',
              'form'          => \Drupal::formBuilder()->getForm(new TrashConfirmDelete($operations, $bathTitle)),
            ],
          ];
        }
        else {
          return \Drupal::service('Basket')->getError(404);
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function deleteOrderProcess($orderId, &$context) {
    $order = \Drupal::service('Basket')->Orders($orderId);
    $order->delete();
  }

  /**
   * {@inheritdoc}
   */
  public static function deleteProductProcess($nid) {
    $entity = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    if (!empty($entity)) {
      $entity->delete();
    }
  }

}

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class TrashConfirmDelete extends FormBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set operations.
   *
   * @var array
   */
  protected $operations;

  /**
   * Set bathTitle.
   *
   * @var string
   */
  protected $bathTitle;

  /**
   * {@inheritdoc}
   */
  public function __construct($operations, $bathTitle) {
    $this->basket = \Drupal::service('Basket');
    $this->operations = $operations;
    $this->bathTitle = $bathTitle;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basket_trash_confirm_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['text'] = [
      '#markup'       => $this->basket->Translate()->t('This action is irreversible!'),
    ];
    $form['actions'] = [
      '#type'         => 'actions',
      'submit'        => [
        '#type'         => 'submit',
        '#value'        => $this->basket->Translate()->t('Delete'),
        '#attributes'   => [
          'class'         => ['button--delete'],
        ],
      ],
      'cancel'        => [
        '#type'           => 'inline_template',
        '#template'       => '<a href="javascript:history.back();" class="form-submit">{{ text }}</a>',
        '#context'        => [
          'text'            => $this->basket->Translate()->t('Cancel'),
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('basket.admin.pages', ['page_type' => 'trash']);
    $batch = [
      'title'             => $this->bathTitle,
      'operations'        => $this->operations,
      'basket_batch'      => TRUE,
    ];
    batch_set($batch);
  }

}
