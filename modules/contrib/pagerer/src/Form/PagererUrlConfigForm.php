<?php

namespace Drupal\pagerer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Main Pagerer URL settings admin form.
 */
class PagererUrlConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pagerer_url_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pagerer.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pagerer.settings');
    $form['pagerer']['core_override_querystring'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('URL querystring'),
      '#description' => $this->t('Pagerer overrides the standard URL querystring "page" key.'),
      '#default_value' => (bool) $config->get('url_querystring.core_override'),
    ];
    $form['pagerer']['querystring_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Querystring key'),
      '#description' => $this->t('The key to be used in the URL querystring, in place of "page". For example, "pg".'),
      '#default_value' => $config->get('url_querystring.querystring_key'),
      '#required' => TRUE,
      '#size' => 6,
      '#maxlength' => 10,
      '#states' => [
        'enabled' => [
          ':input[name="core_override_querystring"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $options = [
      0 => $this->t('Zero-based'),
      1 => $this->t('One-based'),
    ];
    $form['pagerer']['index_base'] = [
      '#type' => 'radios',
      '#title' => $this->t('Page index base'),
      '#description' => $this->t('The number base for the page index in the URL querystring.'),
      '#default_value' => $config->get('url_querystring.index_base'),
      '#options' => $options,
      '#states' => [
        'enabled' => [
          ':input[name="core_override_querystring"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if (in_array((string) $form_state->getValue('querystring_key'), ['page', 'page_ak'])) {
      $form_state->setErrorByName('querystring_key', $this->t("<kbd>'page'</kbd> and <kbd>'page_ak'</kbd> can not be used for replacement. Choose a different key name."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('pagerer.settings');
    $config->set('url_querystring.core_override', (bool) $form_state->getValue('core_override_querystring'));
    $config->set('url_querystring.index_base', (int) $form_state->getValue('index_base'));
    $config->set('url_querystring.querystring_key', (string) $form_state->getValue('querystring_key'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
