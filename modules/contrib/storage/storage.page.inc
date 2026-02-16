<?php

/**
 * @file
 * Contains storage.page.inc.
 *
 * Page callback for Storage entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Storage templates.
 *
 * Default template: storage.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_storage(array &$variables) {
  // Fetch Storage Entity Object.
  $storage = $variables['elements']['#storage'];
  $variables['storage'] = $storage;

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
