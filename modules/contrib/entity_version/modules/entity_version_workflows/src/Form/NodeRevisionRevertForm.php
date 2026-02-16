<?php

namespace Drupal\entity_version_workflows\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Form\NodeRevisionRevertForm as CoreNodeRevisionRevertForm;
use Drupal\node\NodeInterface;

/**
 * Provides a form for reverting a node revision.
 */
class NodeRevisionRevertForm extends CoreNodeRevisionRevertForm {

  /**
   * {@inheritdoc}
   */
  protected function prepareRevertedRevision(NodeInterface $revision, FormStateInterface $form_state) {
    $revision = parent::prepareRevertedRevision($revision, $form_state);
    // When we revert a node revision, we don't want the version values to
    // update.
    $revision->entity_version_no_update = TRUE;
    return $revision;
  }

}
