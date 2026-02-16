<?php

namespace Drupal\basket;

/**
 * {@inheritdoc}
 */
class BasketTerm {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set tree.
   *
   * @var array
   */
  protected $tree;

  /**
   * Set terms.
   *
   * @var array
   */
  protected $terms;

  /**
   * Set getColorOptions.
   *
   * @var array
   */
  protected $getColorOptions;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function tree($type) {
    if (!isset($this->tree[$type])) {
      $this->tree[$type] = \Drupal::database()->select('basket_terms', 't')
        ->fields('t')
        ->condition('t.type', $type)
        ->orderBy('t.weight', 'ASC')
        ->orderBy('t.name', 'ASC')
        ->execute()->fetchAll();
    }
    return $this->tree[$type];
  }

  /**
   * {@inheritdoc}
   */
  public function load($tid) {
    if (!isset($this->terms[$tid])) {
      $this->terms[$tid] = \Drupal::database()->select('basket_terms', 't')
        ->fields('t')
        ->condition('t.id', $tid)
        ->execute()->fetchObject();
      $this->colorAlter($this->terms[$tid]);
    }
    return $this->terms[$tid];
  }

  /**
   * {@inheritdoc}
   */
  private function colorAlter(&$term) {
    if (!empty($term->color) && $term->color == '#ffffff') {
      $term->color = NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($tid) {
    \Drupal::database()->delete('basket_terms')
      ->condition('id', $tid)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions($type) {
    $options = [];
    foreach ($this->tree($type) as $term) {
      $options[$term->id] = $this->basket->Translate()->trans($term->name);
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultNewOrder($tid) {
    $term = $this->load($tid);
    if (!empty($term)) {
      \Drupal::database()->update('basket_terms')
        ->fields([
          'default'       => NULL,
        ])
        ->condition('type', $term->type)
        ->execute();
      \Drupal::database()->update('basket_terms')
        ->fields([
          'default'       => 1,
        ])
        ->condition('id', $term->id)
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultNewOrder($type = NULL) {
    $def = \Drupal::database()->select('basket_terms', 't')
      ->fields('t', ['id'])
      ->condition('t.type', $type)
      ->condition('t.default', 1)
      ->execute()->fetchField();
    return !empty($def) ? $def : NULL;
  }

}
