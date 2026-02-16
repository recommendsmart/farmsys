<?php

/**
 * @file
 * Contains \Drupal\resource\Form\ResourceTypeForm.
 */

namespace Drupal\resource\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ResourceTypeForm.
 *
 * @package Drupal\resource\Form
 */
class ResourceTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $resource_type = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $resource_type->label(),
      '#description' => $this->t("Label for the Resource type."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $resource_type->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\resource\Entity\ResourceType::load',
      ),
      '#disabled' => !$resource_type->isNew(),
    );

    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $resource_type->getDescription(),
      '#description' => $this->t("Resource type description."),
    );

    $form['name_pattern'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name pattern'),
      '#maxlength' => 255,
      '#default_value' => $resource_type->getNamePattern(),
      '#desription' => $this->t('When a resource name is auto-generated, this is the naming pattern that will be used. Available tokens are below.'),
      // @todo: There is no need to require pattern here.
      '#required' => TRUE,
    );

    $form['name_edit'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow name editing'),
      '#default_value' => $resource_type->isNameEditable(),
      '#description' => t('Check this to allow users to edit resource names. Otherwise, resource names will always be auto-generated.'),
    );

    $form['active'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically active'),
      '#default_value' => $resource_type->isAutomaticallyActive(),
      '#description' => t('Automatically mark resources of this type as "active".'),
    );

    $form['new_revision'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Create new revision'),
      '#default_value' => $resource_type->isNewRevision(),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $resource_type = $this->entity;
    $status = $resource_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Resource type.', [
          '%label' => $resource_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Resource type.', [
          '%label' => $resource_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($resource_type->urlInfo('collection'));
  }

}
