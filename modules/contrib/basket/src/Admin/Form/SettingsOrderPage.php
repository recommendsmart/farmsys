<?php

namespace Drupal\basket\Admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class SettingsOrderPage extends FormBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basket_order_page_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = '', $tid = NULL) {
    $form['config'] = [
      '#tree'         => TRUE,
      'view_ajax'     => [
        '#type'         => 'checkbox',
        '#title'        => $this->basket->Translate()->t('Display basket page in popup'),
        '#default_value' => $this->basket->getSettings('order_page', 'config.view_ajax'),
      ],
      'view_form'     => [
        '#type'         => 'checkbox',
        '#title'        => $this->basket->Translate()->t('Display checkout form'),
        '#default_value' => $this->basket->getSettings('order_page', 'config.view_form'),
      ],
    ];
    $form['actions'] = [
      '#type'         => 'actions',
      'submit'        => [
        '#type'         => 'submit',
        '#value'        => t('Save configuration'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->basket->setSettings('order_page', 'config', $form_state->getValue('config'));
  }

}
