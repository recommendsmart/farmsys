<?php

namespace Drupal\config_terms\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides specific access control for the taxonomy_term entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:config_terms_term",
 *   label = @Translation("Config Terms Term selection"),
 *   entity_types = {"config_terms_term"},
 *   group = "default",
 *   weight = 1
 * )
 */
class TermSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    /** @var \Drupal\config_terms\VocabStorageInterface $vocab_storage */
    $vocab_storage = $this->entityTypeManager->getStorage('config_terms_vocab');
    $form['target_vocab'] = [
      '#type' => 'radios',
      '#title' => $this->t('Target vocabulary'),
      '#options' => $vocab_storage->getVocabsList(),
      '#default_value' => isset($this->configuration['handler_settings']['target_vocab']) ? $this->configuration['handler_settings']['target_vocab'] : NULL,
      '#required' => TRUE,
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    if ($match || $limit) {
      return parent::getReferenceableEntities($match, $match_operator, $limit);
    }

    // Limit the selections to terms associated with the selected vocab(s).
    /** @var \Drupal\config_terms\TermStorageInterface $term_storage */
    $term_storage = $this->entityTypeManager->getStorage('config_terms_term');

    $handler_settings = $this->configuration['handler_settings'];
    $vid = isset($handler_settings['target_vocab']) ? $handler_settings['target_vocab'] : FALSE;

    // The return array needs to be keyed by bundle. There is only one.
    $options = ['config_terms_term' => []];
    if ($vid) {
      $options['config_terms_term'] = $term_storage->getTermOptions($vid);
    }

    return $options;
  }

}
