<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

/**
 * Prepares variables for views table templates.
 *
 * Default template: views-view-table.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_view_table_lk_orders(array &$variables) {
  if (function_exists('template_preprocess_views_view_table')) {
    template_preprocess_views_view_table($variables);
  }
  $variables['view']->element['#attached']['library'][] = 'basket/basket.js';
}
