<?php

namespace Drupal\config_terms\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\config_terms\TermStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides confirmation form for resetting a vocabulary to alphabetical order.
 */
class VocabResetForm extends EntityConfirmFormBase {

  /**
   * The term storage.
   *
   * @var \Drupal\config_terms\TermStorageInterface
   */
  protected $termStorage;

  /**
   * Constructs a new VocabularyResetForm object.
   *
   * @param \Drupal\config_terms\TermStorageInterface $term_storage
   *   The term storage.
   */
  public function __construct(TermStorageInterface $term_storage) {
    $this->termStorage = $term_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('config_terms_term')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_terms_vocab_confirm_reset_alphabetical';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to reset the vocab %title to alphabetical order?', ['%title' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl('overview-form');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Resetting a vocab will discard all custom ordering and sort items alphabetically.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Reset to alphabetical');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->termStorage->resetWeights($this->entity->id());

    $this->messenger()->addStatus($this->t('Reset vocab %name to alphabetical order.', ['%name' => $this->entity->label()]));
    $this->logger('config_terms')->notice('Reset vocab %name to alphabetical order.', ['%name' => $this->entity->label()]);
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
