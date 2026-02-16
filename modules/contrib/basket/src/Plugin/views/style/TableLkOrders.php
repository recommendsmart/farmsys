<?php

namespace Drupal\basket\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\Table;

/**
 * Style plugin to render each item as a row in a table.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "table_lk_orders",
 *   title = @Translation("Table LK orders"),
 *   help = @Translation("Displays rows in a table."),
 *   theme = "views_view_table_lk_orders",
 *   display_types = {"normal"}
 * )
 */
class TableLkOrders extends Table {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['botttomFields'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $field_names = $this->displayHandler->getFieldLabels();
    $form['botttomFields'] = [
      '#type'             => 'select',
      '#options'          => $field_names,
      '#default_value'    => $this->options['botttomFields'],
      '#title'            => 'Basket Goods Table Field',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $botttomFields = $this->options['botttomFields'];
    if (!empty($botttomFields)) {
      foreach ($this->view->result as $row_number => $row) {
        if (!empty($this->view->field[$botttomFields])) {
          $build[0]['#rows'][$row_number]->botttomFields = $this->view->field[$botttomFields]->render($row);
        }
      }
    }
    return $build;
  }

}
