<?php

namespace Drupal\entity_taxonomy\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\entity_taxonomy\TermInterface;
use Drupal\entity_taxonomy\VocabularyInterface;

/**
 * Provides route responses for entity_taxonomy.module.
 */
class EntityTaxonomyController extends ControllerBase {

  /**
   * Returns a form to add a new term to a vocabulary.
   *
   * @param \Drupal\entity_taxonomy\VocabularyInterface $entity_taxonomy_vocabulary
   *   The vocabulary this term will be added to.
   *
   * @return array
   *   The entity_taxonomy term add form.
   */
  public function addForm(VocabularyInterface $entity_taxonomy_vocabulary) {
    $term = $this->entityTypeManager()->getStorage('entity_taxonomy_term')->create(['vid' => $entity_taxonomy_vocabulary->id()]);
    return $this->entityFormBuilder()->getForm($term);
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\entity_taxonomy\VocabularyInterface $entity_taxonomy_vocabulary
   *   The vocabulary.
   *
   * @return string
   *   The vocabulary label as a render array.
   */
  public function vocabularyTitle(VocabularyInterface $entity_taxonomy_vocabulary) {
    return ['#markup' => $entity_taxonomy_vocabulary->label(), '#allowed_tags' => Xss::getHtmlTagList()];
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\entity_taxonomy\TermInterface $entity_taxonomy_term
   *   The entity_taxonomy term.
   *
   * @return array
   *   The term label as a render array.
   */
  public function termTitle(TermInterface $entity_taxonomy_term) {
    return ['#markup' => $entity_taxonomy_term->getName(), '#allowed_tags' => Xss::getHtmlTagList()];
  }

}
