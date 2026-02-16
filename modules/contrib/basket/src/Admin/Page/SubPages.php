<?php

namespace Drupal\basket\Admin\Page;

use Drupal\Core\Url;
use Drupal\Core\Render\Markup;
use Drupal\Core\Menu\MenuTreeParameters;

/**
 * {@inheritdoc}
 */
class SubPages {

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
  public function list($pageType = NULL) {
    $elements = [
      'list'          => [
        '#prefix'        => '<div class="sub_pages_list">',
        '#suffix'        => '</div>',
      ],
    ];
    $elementsIsEmpty = TRUE;
    $get_info = $this->getInfo();
    if (!empty($get_info->id)) {
      $menu_tree = \Drupal::menuTree();
      $parameters = new MenuTreeParameters();
      $parameters->addCondition('parent', $get_info->id);
      $parameters->onlyEnabledLinks();
      $tree = \Drupal::service('menu.link_tree')->load('basket', $parameters);

      $subtree = [];
      if (!empty($tree)) {
        if (!empty($tree[key($tree)]->subtree)) {
          $subtree += $tree[key($tree)]->subtree;
          $tree[key($tree)]->subtree = [];
        }
        $subtree[key($tree)] = $tree[key($tree)];
      }
      $manipulators = [
          ['callable' => 'menu.default_tree_manipulators:checkAccess'],
          ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
          ['callable' => '\Drupal\basket\Admin\ManagerMenu::checkAccess'],
      ];
      $subtree = $menu_tree->transform($subtree, $manipulators);
      if (!empty($subtree)) {
        foreach ($subtree as $subItem) {
          if (!$subItem->access->isAllowed()) {
            continue;
          }
          $getPluginDefinition = $subItem->link->getPluginDefinition();
          $elements['list'][] = [
            '#type'         => 'link',
            '#title'        => Markup::create('<span>' . $getPluginDefinition['title'] . '</span>'),
            '#url'          => new Url($getPluginDefinition['route_name'], $getPluginDefinition['route_parameters']),
            '#prefix'        => '<div class="item">',
            '#suffix'        => '</div>',
          ];
          $elementsIsEmpty = FALSE;
        }
      }
    }
    if ($elementsIsEmpty) {
      $elements['list'] = [
        '#prefix'       => '<div class="basket_table_wrap">',
        '#suffix'       => '</div>',
            [
              '#prefix'       => '<div class="b_content">',
              '#suffix'       => '</div>',
              '#markup'       => $this->basket->Translate()->t('The list is empty.'),
            ],
      ];
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  private function getInfo() {
    $currentRoutParams = \Drupal::routeMatch()->getParameters()->all();
    // ---
    $query = \Drupal::database()->select('menu_tree', 'm');
    $query->fields('m', ['parent', 'title', 'id']);
    $query->condition('route_name', \Drupal::routeMatch()->getRouteName());
    if (!empty($currentRoutParams['page_type'])) {
      $query->condition('route_param_key', 'page_type=' . $currentRoutParams['page_type']);
    }
    $query->condition('m.parent', '', '!=');
    return $query->execute()->fetchObject();
  }

}
