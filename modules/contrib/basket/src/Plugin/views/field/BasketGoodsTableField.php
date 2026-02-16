<?php

namespace Drupal\basket\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Render\Element;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Table field for ordered items.
 *
 * @ViewsField("basket_goods_table_field")
 */
class BasketGoodsTableField extends FieldPluginBase {

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
    call_user_func_array(['parent', '__construct'], func_get_args());
    $this->basket = \Drupal::getContainer()->get('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if (empty($values->nid)) {
      return [];
    }
    $order = $this->basket->Orders(NULL, $values->nid)->load();
    $currency = NULL;
    if (!empty($order->currency)) {
      $currency = $this->basket->Currency()->load($order->currency);
    }
    if (!empty($order->items)) {
      $nids = [];
      foreach ($order->items as $key => &$item) {
        $nids[$item->nid] = $item->nid;
      }
    }
    $display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->load('node.' . $values->_entity->getType() . '.basket');
    if (empty($display) || !empty($display) && !$display->status()) {
      $display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->load('node.' . $values->_entity->getType() . '.default');
    }
    $mode = !empty($display) ? $display->getMode() : 'default';
    // ---
    $builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build = $builder->view($values->_entity, $mode);
    $build['#pre_render'][] = [BasketTrustedCallbacks::class, 'preRender'];
    // ---
    $nodes = [];
    if (!empty($nids)) {
      foreach (\Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids) as $node) {
        if ($node->isPublished()) {
          $nodes[$node->id()] = $node;
        }
      }
    }
    return [
      '#theme'        => 'basket_lk_goods_table',
      '#prefix'        => '<div class="basket_lk_goods_table_wrap">',
      '#suffix'        => '</div>',
      '#info'            => [
        'order'            => $order,
        'currency'        => $currency,
        'itemsNodes'    => $nodes,
        'orderNode'        => $values->_entity,
        'build'         => $build,
        'basket_repeat' => $this->basketRepeat($order),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  private function basketRepeat($order) {
    if (\Drupal::moduleHandler()->moduleExists('basket_repeat')) {
      return \Drupal::service('basket_repeat.twig.TwigExtension')->BasketRepeat($order->id);
    }
    return [];
  }

}

/**
 * {@inheritdoc}
 */
class BasketTrustedCallbacks implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRender'];
  }

  /**
   * {@inheritdoc}
   */
  public static function preRender($vars) {
    foreach (Element::children($vars) as $fieldName) {
      switch ($fieldName) {
        case'title':
        case'uid':
        case'created':
          break;

        default:
          if (empty($vars[$fieldName][0])) {
            continue 2;
          }
          $vars['table'][$fieldName] = [
            '#type'         => 'inline_template',
            '#template'     => '{% if value %}
                            <tr class="tr_field_{{ fieldName }}">
                                <td class="td_label">
                                    {{ title }}
                                </td>
                                <td class="td_value">
                                    {{ value }}
                                </td>
                            </tr>
                        {% endif %}',
            '#context'      => [
              'title'         => !empty($vars[$fieldName]['#title']) ? $vars[$fieldName]['#title'] : '',
              'value'         => !empty($vars[$fieldName][0]) ? $vars[$fieldName][0] : '',
              'fieldName'     => $fieldName,
            ],
            '#weight'       => !empty($vars[$fieldName]['#weight']) ? $vars[$fieldName]['#weight'] : 0,
          ];
          break;
      }
      unset($vars[$fieldName]);
    }
    $vars['#theme'] = '';
    return $vars;
  }

}
