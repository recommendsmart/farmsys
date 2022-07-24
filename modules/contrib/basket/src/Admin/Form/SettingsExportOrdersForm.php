<?php

namespace Drupal\basket\Admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class SettingsExportOrdersForm extends FormBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
    $this->settings = $this->basket->getSettings('export_orders', 'config');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basket_export_orders_form_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = '', $tid = NULL) {
    $form['#prefix'] = '<div id="table_excel">';
    $form['#suffix'] = '</div>';
    $form['#attached']['library'][] = 'basket/codemirror';
    $form['config'] = [
      '#tree'         => TRUE,
      'orders'        => [
        '#type'         => 'details',
        '#title'        => $this->basket->Translate()->t('Orders'),
        '#open'         => TRUE,
        'header'        => [
          '#type'         => 'item',
          '#title'        => $this->basket->Translate()->trans('Column name') . ':',
          '0'             => $this->getColumns(['config', 'orders', 'header'], TRUE),
        ],
        'data'          => [
          '#type'         => 'item',
          '#title'        => $this->basket->Translate()->trans('Order data') . ':',
          '0'             => $this->getColumns(['config', 'orders', 'data']),
        ],
        'token'         => [
          '#theme'        => 'token_tree_link',
          '#token_types'  => ['user', 'node'],
          '#text'         => $this->basket->Translate()->t('[available tokens]'),
        ],
        'twig'          => $this->templateTokenTwig(),
      ],
    ];
    // ---
    $form['actions'] = [
      '#type'         => 'actions',
      'submit'        => [
        '#type'         => 'submit',
        '#value'        => t('Save configuration'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->basket->setSettings('export_orders', 'config', $form_state->getValue('config'));
  }

  /**
   * {@inheritdoc}
   */
  private function templateTokenTwig() {
    $tokents = [];
    foreach ($this->basket->getClass('Drupal\basket\BasketExport')->getTokenInfo() as $keyToken => $token) {
      if (!is_array($token)) {
        $tokents[] = '{{' . $keyToken . '}} - <b>' . $this->basket->Translate()->trans(trim($token)) . '</b>';
      }
      else {
        $tokents[] = '{{' . $keyToken . '}} - <b>' . $this->basket->Translate()->trans(trim($token['title'])) . '</b>';
      }
    }
    return [
      '#type'         => 'details',
      '#title'        => $this->basket->Translate()->t('Twig tokens'),
      '#description'  => implode('<br/>', $tokents),
    ];
  }

  /**
   * {@inheritdoc}
   */
  private function getColumns($parents, $isHeader = FALSE) {
    $header = [''];
    $rows[0] = [
      '#markup'       => '1',
      '#wrapper_attributes' => ['class' => ['td_num', 'not_hover']],
    ];
    foreach (range('A', 'Z') as $letter) {
      $header[] = $letter;
      $rows[$letter]['data'] = [
        '#type'         => 'textfield',
        '#parents'      => $parents + ['letter' => $letter],
        '#default_value' => !empty($this->settings[$parents[1]][$parents[2]][$letter]) ? $this->settings[$parents[1]][$parents[2]][$letter] : '',
      ];
      if (!$isHeader) {
        $rows[$letter]['data']['#type'] = 'textarea';
        $rows[$letter]['data']['#attributes']['class'][] = 'inline_twig inline_twig_excel';
        $rows[0]['#wrapper_attributes']['height'] = 100;
      }
    }
    $res = [
      '#type'         => 'table',
      '#header'       => $header,
      '#prefix'       => '<div class="table_excel_wrap ' . implode('_', $parents) . '">',
      '#suffix'       => '</div>',
    ];
    $res += [$rows];
    return $res;
  }

}
