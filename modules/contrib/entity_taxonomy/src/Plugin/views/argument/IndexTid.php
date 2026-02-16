<?php

namespace Drupal\entity_taxonomy\Plugin\views\argument;

use Drupal\entity_taxonomy\Entity\Term;
use Drupal\views\Plugin\views\argument\ManyToOne;

/**
 * Allow entity_taxonomy term ID(s) as argument.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("entity_taxonomy_index_tid")
 */
class IndexTid extends ManyToOne {

  public function titleQuery() {
    $titles = [];
    $terms = Term::loadMultiple($this->value);
    foreach ($terms as $term) {
      $titles[] = \Drupal::service('entity.repository')->getTranslationFromContext($term)->label();
    }
    return $titles;
  }

}
