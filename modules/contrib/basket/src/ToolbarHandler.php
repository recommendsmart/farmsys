<?php

namespace Drupal\basket;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Template\Attribute;

/**
 * {@inheritdoc}
 */
class ToolbarHandler implements ContainerInjectionInterface {

  /**
   * Set menuLinkTree.
   *
   * @var object
   */
  protected $menuLinkTree;

  /**
   * Set account.
   *
   * @var object
   */
  protected $account;

  /**
   * Set trans.
   *
   * @var object
   */
  protected $trans;

  /**
   * {@inheritdoc}
   */
  public function __construct(MenuLinkTreeInterface $menu_link_tree, ConfigFactoryInterface $config_factory, AccountProxyInterface $account) {
    $this->menuLinkTree = $menu_link_tree;
    $this->account = $account;
    $this->trans = \Drupal::service('Basket')->Translate();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('toolbar.menu_tree'),
      $container->get('config.factory'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function toolbar() {
    $items['basket'] = [
      '#cache'         => [
        'contexts'         => ['user.permissions'],
      ],
    ];
    if ($this->account->hasPermission('basket order_access')) {
      $class = [
        'toolbar-item',
        'basket-toolbar-item'
      ];
      if (function_exists('_gin_toolbar_gin_is_active') && _gin_toolbar_gin_is_active()) {
        $class[] = 'toolbar-icon';
        $class[] = 'toolbar-icon-commerce-admin-commerce';
      }
      $items['basket'] += [
        '#type'         => 'toolbar_item',
        '#weight'       => 999,
        'tab'           => [
          '#type'         => 'link',
          '#title'        => $this->trans->t('Shop'),
          '#url'          => new Url('basket.admin.pages'),
          '#options'      => [
            'attributes'    => [
              'class'         => $class
            ],
          ],
        ],
      ];
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessMenu(&$vars) {
    $links['basket'] = [
      'is_expanded' => TRUE,
      'title'       => t('Shop', [], ['context' => 'basket']),
      'url'         => Url::fromRoute('basket.admin.pages', [], [
        'attributes'    => [
          'class'       => [
            'toolbar-icon-commerce-admin-commerce',
            'toolbar-icon',
            \Drupal::routeMatch()->getRouteName() == 'basket.admin.pages' ? 'is-active' : ''
          ],
        ]
      ]),
      'attributes'  => new Attribute([
        'title'       => t('Shop', [], ['context' => 'basket'])
      ])
    ];
    $tree = \Drupal::menuTree()->load('basket', new \Drupal\Core\Menu\MenuTreeParameters());
    if(!empty($tree['basket.info']->subtree)) {
      foreach ($tree['basket.info']->subtree as $key => $subtree) {
        $links['basket']['below'][$key] = [
          'title'         => $subtree->link->getTitle(),
          'url'           => $subtree->link->getUrlObject()->setOptions([]),
          'attributes'    => new Attribute()
        ];
      }
    }
    $this->arraySpliceAssoc($vars['items'], 1, 0, $links);
  }

  public function arraySpliceAssoc(&$input, $offset, $length = 0, $replacement = array()) {
    $keys = array_keys($input);
    $values = array_values($input);
    array_splice($keys, $offset, $length, array_keys($replacement));
    array_splice($values, $offset, $length, array_values($replacement));
    $input = array_combine($keys, $values);
  }

}
