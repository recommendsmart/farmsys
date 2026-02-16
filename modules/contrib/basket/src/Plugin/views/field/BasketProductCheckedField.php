<?php

namespace Drupal\basket\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Session\AccountInterface;

/**
 * Product selection field.
 *
 * @ViewsField("basket_product_checked_field")
 */
class BasketProductCheckedField extends FieldPluginBase {

  /**
   * Called to add the field to a query.
   */
  public function query() {
    // We don't need to modify query for this particular example.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $nid = $values->_entity->id();
    return [
      '#type'                => 'checkbox',
      '#title'            => $nid,
      '#name'                => 'product_chacked[' . $nid . ']',
      '#id'                  => 'product_chacked_' . $nid,
      '#return_value' => $nid,
      '#attributes'      => [
        'class'               => ['not_label'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function elementClasses($row_index = NULL) {
    return 'td_settings_row';
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    if (\Drupal::routeMatch()->getRouteName() == 'views_ui.form_display') {
      return !empty($this->options['label']) ? $this->options['label'] : '';
    }
    $element = [
      '#type'            => 'checkbox',
      '#title'        => '+',
      '#attributes'    => [
        'class'            => ['not_label'],
        'onchange'        => 'basket_admin_checked_all(this, \'product_chacked[\')',
      ],
      '#id'            => 'product_chacked_all',
    ];
    return \Drupal::service('renderer')->render($element);
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    if (!empty($this->view->args[0]) && $this->view->args[0] == 'is_delete') {
      return FALSE;
    }
    return $account->hasPermission('basket operations product');
  }

}
