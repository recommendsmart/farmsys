<?php

namespace Drupal\config_terms;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of Config term vocab entities.
 *
 * @see \Drupal\config_terms\Entity\Vocab
 */
class VocabListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'vocabs';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_terms_overview_vocabs';
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = $this->t('Edit vocab');
    }

    $operations['list'] = [
      'title' => $this->t('List terms'),
      'weight' => 0,
      'url' => $entity->toUrl('overview-form'),
    ];
    $operations['add'] = [
      'title' => $this->t('Add terms'),
      'weight' => 10,
      'url' => Url::fromRoute('entity.config_terms_term.add_form', ['config_terms_vocab' => $entity->id()]),
    ];
    unset($operations['delete']);

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];
    $header['label'] = $this->t('Vocab name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];
    $row['label'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $entities = $this->load();
    // If there are not multiple vocabularies, disable dragging by unsetting the
    // weight key.
    if (count($entities) <= 1) {
      unset($this->weightKey);
    }
    $build = parent::render();
    $url = Url::fromRoute('entity.config_terms_vocab.add_form');
    $build['table']['#empty'] = $this->t('No vocabs available. <a href=":link">Add vocab</a>.', [':link' => $url->toString()]);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['vocabularies']['#attributes'] = ['id' => 'config-terms'];
    $form['actions']['submit']['#value'] = $this->t('Save');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->messenger()->addStatus($this->t('The configuration options have been saved.'));
  }

}
