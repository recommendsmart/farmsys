<?php

namespace Drupal\field_suggestion\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the field suggestion edit forms.
 */
class FieldSuggestionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\field_suggestion\FieldSuggestionInterface $entity */
    $entity = $this->getEntity();

    if ($entity->isIgnored()) {
      foreach (['once', 'exclude'] as $field) {
        if (isset($form[$field])) {
          $form[$field]['#access'] = FALSE;
        }
      }
    }

    return $form;
  }

}
