<?php

namespace Drupal\basket\Admin;

use Drupal\Core\Url;

/**
 * {@inheritdoc}
 */
class ManagerHeader {

  /**
   * Set currentRoutName.
   *
   * @var string
   */
  protected $currentRoutName;

  /**
   * Set currentRoutParams.
   *
   * @var array
   */
  protected $currentRoutParams;

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
    $this->currentRoutName = \Drupal::routeMatch()->getRouteName();
    $this->currentRoutParams = \Drupal::routeMatch()->getParameters()->all();
  }

  /**
   * {@inheritdoc}
   */
  public function block($createLink = []) {
    return [
      '#theme'        => 'basket_admin_header',
      '#info'         => [
        'breadcrumbs'   => $this->getBreadcrumbs(),
        'items'         => [
          [
            'block'     => \Drupal::service('plugin.manager.block')->createInstance('basket_currency')->build(),
          ], [
            '#type'       => 'link',
            '#title'      => $this->basket->Translate()->t('To the site'),
            '#url'        => new Url('<front>', [], [
              'ico_name'    => 'home.svg',
            ]),
          ], [
            '#type'       => 'link',
            '#title'      => $this->basket->Translate()->t('Exit'),
            '#url'        => new Url('user.logout', [], [
              'ico_name'    => 'user.svg',
            ]),
          ],
        ],
        'CreateLink'      => $createLink,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  private function getBreadcrumbs() {
    $items = [];
    $this->getBreadcrumbsItems($items);
    return !empty($items) ? array_reverse($items) : [];
  }

  /**
   * {@inheritdoc}
   */
  private function getBreadcrumbsItems(&$items, $parent_id = NULL) {
    $query = \Drupal::database()->select('menu_tree', 'm');
    $query->fields('m', ['parent', 'title', 'id']);
    if (is_null($parent_id)) {
      $query->condition('route_name', $this->currentRoutName);
      if (!empty($this->currentRoutParams['page_type'])) {
        $query->condition('route_param_key', 'page_type=' . $this->currentRoutParams['page_type']);
      }
      $query->condition('m.parent', '', '!=');
    }
    else {
      $query->condition('m.id', $parent_id);
    }
    $get_info = $query->execute()->fetchObject();
    if (!empty($get_info)) {
      $items[] = unserialize($get_info->title);
      if (!empty($get_info->parent)) {
        $this->getBreadcrumbsItems($items, $get_info->parent);
      }
    }
  }

}
