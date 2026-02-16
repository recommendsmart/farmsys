<?php

namespace Drupal\element\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ElementTypeForm.
 */
class ElementTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\element\Entity\ElementType $elementType */
    $elementType = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $elementType->label(),
      '#description' => $this->t('The human-readable name of this element type.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $elementType->id(),
      '#machine_name' => [
        'exists' => '\Drupal\element\Entity\ElementType::load',
      ],
      '#disabled' => !$elementType->isNew(),
      '#description' => $this->t('A unique machine-readable name for this element type. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#maxlength' => 255,
      '#default_value' => $elementType->getDescription(),
      '#description' => $this->t('Describe this element type. The text will be displayed on the <em>Add new element</em> page.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $elementType = $this->entity;
    $replacements = ['%label' => $elementType->label()];

    try {
      $status = $elementType->save();

      switch ($status) {
        case SAVED_NEW:
          $this->messenger()->addMessage($this->t('Created the %label element type.', $replacements));
          break;

        default:
          $this->messenger()->addMessage($this->t('Saved the %label element type.', $replacements));
      }
    }
    catch (EntityStorageException $e) {
      $this->messenger()->addMessage($this->t('A problem occurred trying to create or update the %label element type.', $replacements));
      watchdog_exception('element', $e, 'A problem occurred trying to create or update the %label element type.', $replacements);
    }

    try {
      $form_state->setRedirectUrl($elementType->toUrl('collection'));
    }
    catch (EntityMalformedException $e) {
      watchdog_exception('element', $e, 'Could not get collection URL for element types.');
    }
  }

}
