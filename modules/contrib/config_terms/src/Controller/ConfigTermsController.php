<?php

namespace Drupal\config_terms\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\config_terms\Entity\VocabInterface;
use Drupal\config_terms\Entity\TermInterface;

/**
 * Provides route responses for config_terms.module.
 */
class ConfigTermsController extends ControllerBase {

  /**
   * Returns a form to add a new term to a vocab.
   *
   * @param \Drupal\config_terms\Entity\VocabInterface $config_terms_vocab
   *   The vocab this config term will be added to.
   *
   * @return array
   *   The config term add form.
   */
  public function addForm(VocabInterface $config_terms_vocab) {
    $term = $this->entityTypeManager()->getStorage('config_terms_term')->create(['vid' => $config_terms_vocab->id()]);
    return $this->entityFormBuilder()->getForm($term);
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\config_terms\Entity\VocabInterface $config_terms_vocab
   *   The vocab.
   *
   * @return string
   *   The vocab label as a render array.
   */
  public function vocabTitle(VocabInterface $config_terms_vocab) {
    return ['#markup' => $config_terms_vocab->label(), '#allowed_tags' => Xss::getHtmlTagList()];
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\config_terms\Entity\TermInterface $config_term
   *   The config term.
   *
   * @return array
   *   The term label as a render array.
   */
  public function termTitle(TermInterface $config_term = NULL) {
    return ['#markup' => $config_term->getName(), '#allowed_tags' => Xss::getHtmlTagList()];
  }

}
