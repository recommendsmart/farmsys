<?php

namespace Drupal\typed_telephone\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Main form for adding or editing TelephoneType entities.
 */
class TelephoneTypeEntityForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $telephone_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 30,
      '#default_value' => $telephone_type->label(),
      '#description' => $this->t("Label for the Telephone type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $telephone_type->id(),
      '#maxlength' => 30,
      '#machine_name' => [
        'exists' => '\Drupal\typed_telephone\Entity\TelephoneTypeEntity::load',
      ],
      '#disabled' => !$telephone_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $telephone_type = $this->entity;
    $status = $telephone_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Telephone type.', [
          '%label' => $telephone_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Telephone type.', [
          '%label' => $telephone_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($telephone_type->toUrl('collection'));
  }

}
