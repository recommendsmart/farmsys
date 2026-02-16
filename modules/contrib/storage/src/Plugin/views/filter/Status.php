<?php

namespace Drupal\storage\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filter by published status.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("storage_status")
 */
class Status extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {}

  /**
   * {@inheritdoc}
   */
  protected function operatorForm(&$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $table = $this->ensureMyTable();
    $snippet = "$table.status = 1 OR ($table.user_id = ***CURRENT_USER*** AND ***CURRENT_USER*** <> 0 AND ***VIEW_OWN_UNPUBLISHED_STORAGE*** = 1) OR ***ADMINISTER_STORAGE_ENTITIES*** = 1";
    $this->query->addWhereExpression($this->options['group'], $snippet);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'user';
    return $contexts;
  }

}
