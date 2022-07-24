<?php

namespace Drupal\basket\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Views;

/**
 * Filter by SID.
 *
 * @ViewsFilter("basket_is_add_cart")
 */
class BasketIsAddCart extends FilterPluginBase {
	
	/**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['min'] = ['default' => '-30 days'];
    return $options;
  }
	
	/**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
		$options = [];
		foreach (range(1, 30) as $day) {
			$options['-'.$day.' days'] = '-'.$day.' days';
		}
    $form['min'] = [
      '#type'           => 'select',
	    '#options'        => $options,
      '#title'          => 'Max Days',
      '#default_value'  => $this->options['min'],
    ];
  }
	
  /**
   * {@inheritdoc}
   */
  public function query() {
    // Node Is Add To Cart.
    $join = Views::pluginManager('join')->createInstance('standard', [
      'type'           => 'INNER',
      'table'          => 'basket',
      'field'          => 'nid',
      'left_table'     => 'node_field_data',
      'left_field'     => 'nid'
    ]);
    $this->query->addRelationship($this->realField, $join, 'node_field_data');
    // Group.
    $this->query->addField('node_field_data', 'nid', 'n_nid', ['function' => 'groupby']);
    $this->query->addGroupBy("node_field_data.nid");
  }

}
