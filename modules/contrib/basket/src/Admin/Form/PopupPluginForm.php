<?php

namespace Drupal\basket\Admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class PopupPluginForm extends FormBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set trans.
   *
   * @var object
   */
  protected $trans;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
    $this->trans = $this->basket->Translate();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basket_popup_plugin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = '', $tid = NULL) {
    $options = [];
    foreach (\Drupal::service('BasketPopup')->getDefinitions() as $key => $info) {
      $options[$key] = $info['name'];
    }
    $form['config'] = [
      '#tree'         => TRUE,
      'admin'         => [
        '#type'         => 'select',
        '#title'        => $this->trans->t('Pop-up windows in admin panel'),
        '#options'      => $options,
        '#required'     => TRUE,
        '#default_value' => $this->basket->getSettings('popup_plugin', 'config.admin'),
      ],
      'site'          => [
        '#type'         => 'select',
        '#title'        => $this->trans->t('Pop-ups on the site'),
        '#options'      => $options,
        '#required'     => TRUE,
        '#default_value' => $this->basket->getSettings('popup_plugin', 'config.site'),
      ],
      'add_popup'     => [
        '#type'         => 'item',
        '#title'        => $this->trans->t('Popup after adding to cart'),
        'type'          => [
          '#type'         => 'select',
          '#options'      => [
            'noty_message'  => $this->trans->t('Popup message'),
          ],
          '#empty_option' => $this->trans->t('Basic popup'),
          '#default_value' => $this->basket->getSettings('popup_plugin', 'config.add_popup.type'),
        ],
        'noty_message'  => [
          '#type'         => 'textarea',
          '#rows'         => 1,
          '#title'        => $this->trans->t('Message'),
          '#default_value' => $this->basket->getSettings('popup_plugin', 'config.add_popup.noty_message'),
          '#attributes'   => [
            'placeholder'       => 't( )',
            'title'             => 't( )',
          ],
          '#states'       => [
            'visible'       => ['select[name="config[add_popup][type]"]' => ['value' => 'noty_message']],
            'required'      => ['select[name="config[add_popup][type]"]' => ['value' => 'noty_message']],
          ],
        ],
      ],
    ];
    $form['actions'] = [
      '#type'         => 'actions',
      'submit'        => [
        '#type'         => 'submit',
        '#value'        => $this->trans->t('Save'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->basket->setSettings('popup_plugin', 'config', $form_state->getValue('config'));
  }

}
