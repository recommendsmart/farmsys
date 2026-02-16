<?php

namespace Drupal\book_pdf\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Book PDF settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'book_pdf_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['book_pdf.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['basic_auth'] = [
      '#type' => 'fieldset',
      '#title' => 'Basic authentication',
      '#description' => 'Configure the Basic auth username and password phpwkhtmltopdf should use fetching remote assets while generating the PDF',
    ];
    $form['basic_auth']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $this->config('book_pdf.settings')->get('basic_user'),
    ];
    $form['basic_auth']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $this->config('book_pdf.settings')->get('basic_pass'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('book_pdf.settings')
      ->set('basic_user', $form_state->getValue('username'))
      ->set('basic_pass', $form_state->getValue('password'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
