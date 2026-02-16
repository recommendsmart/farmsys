<?php

namespace Drupal\friggeri_cv\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the profile entity edit forms.
 */
class ProfileForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['vertical_tabs'] = [
      '#type' => 'vertical_tabs',
      '#weight' => 99,
    ];
    $form['contact_box_tab'] = [
      '#type' => 'details',
      '#title' => $this->t("Left column"),
      '#group' => 'vertical_tabs',
    ];
    $form['contact_box_tab']['contact_box'] = $form['contact_box'];
    unset($form['contact_box']);

    $form['entity_box_tab'] = [
      '#type' => 'details',
      '#title' => $this->t("Main sections"),
      '#group' => 'vertical_tabs',
    ];
    $form['entity_box_tab']['entity_box'] = $form['sections'];
    unset($form['sections']);

    $form['footer_tab'] = [
      '#type' => 'details',
      '#title' => $this->t("Footer columns"),
      '#group' => 'vertical_tabs',
    ];

    $form['footer_tab']['tabs'] = [
      '#type' => 'horizontal_tabs',
      '#default_tab' => 'edit-col-1',
    ];

    $form['footer_tab']['col_1'] = [
      '#type' => 'details',
      '#title' => $this->t('Column 1'),
      '#group' => 'tabs',
    ];

    $form['footer_tab']['col_1']['footer_col_1_title'] = $form['footer_col_1_title'];
    $form['footer_tab']['col_1']['footer_col_1_title_color'] = $form['footer_col_1_title_color'];
    $form['footer_tab']['col_1']['footer_col_1_colored_letter_number'] = $form['footer_col_1_colored_letter_number'];
    $form['footer_tab']['col_1']['footer_col_1_items'] = $form['footer_col_1_items'];
    unset($form['footer_col_1_title']);
    unset($form['footer_col_1_title_color']);
    unset($form['footer_col_1_colored_letter_number']);
    unset($form['footer_col_1_items']);

    $form['footer_tab']['col_2'] = [
      '#type' => 'details',
      '#title' => $this->t('Column 2'),
      '#group' => 'tabs',
    ];

    $form['footer_tab']['col_2']['footer_col_2_title'] = $form['footer_col_2_title'];
    $form['footer_tab']['col_2']['footer_col_2_title_color'] = $form['footer_col_2_title_color'];
    $form['footer_tab']['col_2']['footer_col_2_colored_letter_number'] = $form['footer_col_2_colored_letter_number'];
    $form['footer_tab']['col_2']['footer_col_2_items'] = $form['footer_col_2_items'];
    unset($form['footer_col_2_title']);
    unset($form['footer_col_2_title_color']);
    unset($form['footer_col_2_colored_letter_number']);
    unset($form['footer_col_2_items']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => render($link)];

    if ($result == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New profile %label has been created.', $message_arguments));
      $this->logger('friggeri_cv')->notice('Created new profile %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The profile %label has been updated.', $message_arguments));
      $this->logger('friggeri_cv')->notice('Updated new profile %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.profile.canonical', ['profile' => $entity->id()]);
  }

}
