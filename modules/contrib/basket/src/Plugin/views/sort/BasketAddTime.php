<?php

namespace Drupal\basket\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Default implementation of the base sort plugin.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("basket_add_time")
 */
class BasketAddTime extends SortPluginBase {

  /**
   * Called to add the field to a query.
   */
  public function query() {
    $this->ensureMyTable();
    // Add the field.
    $this->query->addOrderBy($this->tableAlias, $this->realField, $this->options['order']);
    $this->query->addOrderBy($this->tableAlias, 'id', 'DESC');
  }

}
