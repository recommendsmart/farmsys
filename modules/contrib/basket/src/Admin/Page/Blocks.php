<?php

namespace Drupal\basket\Admin\Page;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Url;

/**
 * {@inheritdoc}
 */
class Blocks {

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
  public function payments() {
    $items = [];
    $paymentServices = Yaml::decode(file_get_contents(\Drupal::service('extension.list.module')->getPath('basket') . '/config/basket_install/payments.yml'));
    $paymentSystems = \Drupal::service('BasketPayment')->getDefinitions();
    if (!empty($paymentServices)) {
      $onclick = 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-help'])->toString() . '\')';
      foreach ($paymentServices as $id => $info) {
        $context = [
          'ico'               => !empty($info['ico']) ? $this->basket->getIco($info['ico']) : '',
          'name'              => !empty($info['name']) ? $info['name'] : '',
          'url'                => 'javascript:void(0);',
        ];
        if (empty($context['ico']) && empty($context['name'])) {
          $context['name'] = $id;
        }
        if (!empty($paymentSystems[$id])) {
          $context['onclick'] = '';
          $moduleInfo = \Drupal::service('extension.list.module')->getExtensionInfo($paymentSystems[$id]['provider']);
          if (!empty($moduleInfo['configure'])) {
            $context['url'] = Url::fromRoute($moduleInfo['configure'])->toString();
          }
        }
        else {
          $context['disabled'] = 'disabled';
          $context['onclick'] = $onclick;
        }
        $items[] = [
          '#type'         => 'inline_template',
          '#template'     => '',
          '#template'         => '<a href="{{ url }}" title="{{ name }}" class="{{ disabled }}" onclick="{{ onclick }}"><span>{% if ico %}{{ ico|raw }}{% else %}{{ name }}{% endif %}</span></a>',
          '#context'      => $context,
          '#prefix'       => '<div class="item">',
          '#suffix'       => '</div>',
        ];
      }
    }
    if (empty($items)) {
      return [];
    }
    return [
      '#prefix'       => '<div class="basket_table_wrap">',
      '#suffix'       => '</div>',
      'title'         => [
        '#prefix'       => '<div class="b_title">',
        '#suffix'       => '</div>',
        '#markup'       => $this->basket->Translate()->t('Payment systems'),
      ],
      'content'       => [
        '#prefix'       => '<div class="sub_pages_list">',
        '#suffix'       => '</div>',
        'items'         => $items,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function deliverys() {
    $items = [];
    $deliveryServices = Yaml::decode(file_get_contents(drupal_get_path('module', 'basket') . '/config/basket_install/deliverys.yml'));
    $deliverySystems = \Drupal::service('BasketDelivery')->getDefinitions();
    if (!empty($deliveryServices)) {
      $onclick = 'basket_admin_ajax_link(this, \'' . Url::fromRoute('basket.admin.pages', ['page_type' => 'api-help'])->toString() . '\')';
      foreach ($deliveryServices as $id => $info) {
        $context = [
          'ico'               => !empty($info['ico']) ? $this->basket->getIco($info['ico']) : '',
          'name'              => !empty($info['name']) ? $info['name'] : '',
          'url'                => 'javascript:void(0);',
        ];
        if (empty($context['ico']) && empty($context['name'])) {
          $context['name'] = $id;
        }
        if (!empty($deliverySystems[$id])) {
          $context['onclick'] = '';
          $moduleInfo = \Drupal::service('extension.list.module')->getExtensionInfo($deliverySystems[$id]['provider']);
          if (!empty($moduleInfo['configure'])) {
            $context['url'] = Url::fromRoute($moduleInfo['configure'])->toString();
          }
        }
        else {
          $context['disabled'] = 'disabled';
          $context['onclick'] = $onclick;
        }
        $items[] = [
          '#type'         => 'inline_template',
          '#template'     => '',
          '#template'         => '<a href="{{ url }}" title="{{ name }}" class="{{ disabled }}" onclick="{{ onclick }}">
                        <span>{% if ico %}{{ ico|raw }}{% else %}{{ name }}{% endif %}</span>
                    </a>',
          '#context'      => $context,
          '#prefix'       => '<div class="item">',
          '#suffix'       => '</div>',
        ];
      }
    }
    if (empty($items)) {
      return [];
    }
    return [
      '#prefix'       => '<div class="basket_table_wrap">',
      '#suffix'       => '</div>',
      'title'         => [
        '#prefix'       => '<div class="b_title">',
        '#suffix'       => '</div>',
        '#markup'       => $this->basket->Translate()->t('Delivery services'),
      ],
      'content'       => [
        '#prefix'       => '<div class="sub_pages_list">',
        '#suffix'       => '</div>',
        'items'         => $items,
      ],
    ];
  }

}
