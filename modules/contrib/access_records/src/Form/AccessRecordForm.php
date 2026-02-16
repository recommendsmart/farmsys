<?php

namespace Drupal\access_records\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\access_records\AccessRecordInterface;

/**
 * Form controller for the access record edit forms.
 */
class AccessRecordForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['#entity_builders']['apply_label_pattern'] = [
      static::class,
      'applyLabelPattern',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'), 'canonical')->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => \Drupal::service('renderer')->render($link)];

    if ($result == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New access record %label has been created.', $message_arguments));
      $this->logger('access_records')->notice('Created new access record %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The access record %label has been updated.', $message_arguments));
      $this->logger('access_records')->notice('Updated new access record %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.access_record.collection');
  }

  /**
   * Entity builder callback that applies the label pattern.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param \Drupal\access_records\AccessRecordInterface $entity
   *   The entity updated with the submitted values.
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function applyLabelPattern($entity_type_id, AccessRecordInterface $entity, array $form, FormStateInterface $form_state) {
    if (!isset($entity->original) && !$entity->isNew()) {
      // Load the unchanged values from the database in order to access
      // previous values.
      $entity->original = \Drupal::entityTypeManager()
        ->getStorage($entity->getEntityTypeId())->loadUnchanged($entity->id());
    }
    $entity->applyLabelPattern();
    // Disable the label pattern afterwards, in order to avoid redundant
    // rebuilds during the save operation chain.
    if ($entity->hasField('label_pattern')) {
      $entity->get('label_pattern')->setValue('');
    }
    else {
      $entity->label_pattern = '';
    }
  }

}
