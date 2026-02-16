<?php

namespace Drupal\basket\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Database\Query\Condition;

/**
 * Item price field.
 *
 * @ViewsField("basket_get_price_field")
 */
class BasketGetPriceField extends FieldPluginBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;
  
  /**
   * Set basketQuery.
   *
   * @var object
   */
  protected $basketQuery;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    call_user_func_array(['parent', '__construct'], func_get_args());
    $this->basket = \Drupal::getContainer()->get('Basket');
    $this->basketQuery = \Drupal::getContainer()->get('BasketQuery');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['templatePrice'] = ['default' => '{{ price|number_format(2, \',\', \' \') }} {{ basket_t(currency) }}'];
    $options['keyPriceField'] = ['default' => 'MIN'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['keyPriceField'] = [
      '#type'             => 'select',
      '#title'            => $this->basket->Translate()->t('Display product price'),
      '#options'          => [
        'MIN'               => 'MIN',
        'MAX'               => 'MAX',
        'FIRST'             => 'First',
      ],
      '#default_value'    => $this->options['keyPriceField'],
    ];
    $form['templatePrice'] = [
      '#type'             => 'textarea',
      '#title'            => 'Render template (Twig)',
      '#rows'             => 1,
      '#default_value'    => $this->options['templatePrice'],
      '#description'      => implode('<br/>', [
        '{{ price }}',
        '{{ old_price }}',
        '{{ currency }}',
      ]),
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->basketQuery->priceViewsJoin($this, $this->options['keyPriceField']);
  }

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {
    $this->basketQuery->priceViewsJoinSort($this, $order, $this->options['keyPriceField']);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return [
      '#type'     => 'inline_template',
      '#template' => $this->options['templatePrice'],
      '#context'  => [
        'price'     => !empty($values->basket_node_priceconvert) ? $values->basket_node_priceconvert : 0,
        'old_price' => !empty($values->basket_node_priceconvertOLd) ? $values->basket_node_priceconvertOLd : 0,
        'currency'  => $this->basket->cart()->getCurrencyName(),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    if (!empty($values) && strpos($this->options['templatePrice'], 'old_price') !== FALSE) {
      $subQuery = $this->basketQuery->getPriceQuery('MIN');
      if (!empty($subQuery)) {
        $query = \Drupal::database()->select('node_field_data', 'n');
        $query->innerJoin($subQuery, 'getPriceQuery', 'getPriceQuery.nid = n.nid');
        $query->fields('getPriceQuery', ['nid', 'priceConvertOld']);
        $db_or = new Condition('OR');
        foreach ($values as $row) {
          $db_and = new Condition('OR');
          $db_or->condition($db_and
            ->condition('n.nid', $row->nid)
            ->condition('getPriceQuery.priceConvert', $row->basket_node_priceconvert)
          );
        }
        $query->condition($db_or);
        $getOldPrices = $query->execute()->fetchAllKeyed();
        if (!empty($getOldPrices)) {
          foreach ($values as &$row) {
            if (!empty($getOldPrices[$row->nid])) {
              $row->basket_node_priceconvertOLd = $getOldPrices[$row->nid];
            }
          }
        }
      }
    }
  }

}
