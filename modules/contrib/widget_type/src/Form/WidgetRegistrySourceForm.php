<?php

namespace Drupal\widget_type\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Widget Registry Source form.
 *
 * @property \Drupal\widget_type\WidgetRegistrySourceInterface $entity
 */
class WidgetRegistrySourceForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('Label for the widget registry source.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\widget_type\Entity\WidgetRegistrySource::load',
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $this->entity->status(),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $this->entity->get('description'),
      '#description' => $this->t('Description of the widget registry source.'),
    ];

    $form['endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Endpoint URL'),
      '#description' => $this->t('The URL to the JSON document containing all the widget metadata.'),
      '#default_value' => $this->entity->get('endpoint'),
      '#size' => 80,
      '#maxlength' => 255,
      '#placeholder' => 'https://www.example.org/widget-registry/production.json',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $message = $result == SAVED_NEW
      ? $this->t('Created new widget registry source %label.', $message_args)
      : $this->t('Updated widget registry source %label.', $message_args);
    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
