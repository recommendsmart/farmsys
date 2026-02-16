<?php

namespace Drupal\basket\Admin\Page;

/**
 * {@inheritdoc}
 */
class StatisticsBuyers {

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
    if (!$this->basket->full('StatisticsBuyers')) {
      return $this->basket->getError(404);
    }
    if (!\Drupal::currentUser()->hasPermission('administer users')) {
      return $this->basket->getError(403);
    }
    return [
      'statistics'    => $this->basket->full('getStatisticsBlock'),
      'users'            => [
        '#prefix'        => '<div class="basket_table_wrap">',
        '#suffix'        => '</div>',
        'title'            => [
          '#prefix'        => '<div class="b_title">',
          '#suffix'        => '</div>',
          '#markup'        => $this->basket->Translate()->t('Customer list'),
        ],
        'content'        => [
          '#prefix'        => '<div class="b_content">',
          '#suffix'        => '</div>',
          'view'            => $this->basket->getView('basket_users', 'block_1'),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function formAlter(&$form, $form_state) {
    $entity = $form_state->getBuildInfo()['callback_object']->getEntity();
    if (!empty($entity->basket_create_user)) {
      $form['actions']['submit']['#submit'][] = __CLASS__ . '::basketCreateAdminUser';
      if (!empty($form['actions']['delete'])) {
        unset($form['actions']['delete']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function basketCreateAdminUser($form, $form_state) {
    $form_state->setRedirect('basket.admin.pages', [
      'page_type'            => 'statistics-buyers',
    ]);
  }

}
