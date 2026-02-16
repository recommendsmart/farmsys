<?php

namespace Drupal\basket\Admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class EmptyTrashSettingsForm extends FormBase {

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
    return 'empty_trash_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['config'] = [
      '#tree'                 => TRUE,
      'delete_nodes'          => [
        '#type'                 => 'checkbox',
        '#title'                => $this->basket->Translate()->t('Empty items in the basket'),
        '#default_value'        => $this->basket->getSettings('empty_trash', 'config.delete_nodes'),
      ],
      'delete_anonim'         => [
        '#type'                 => 'number',
        '#title'                => $this->basket->Translate()->trans('Delete, abandoned goods anonymously, through') . ': ',
        '#default_value'        => $this->basket->getSettings('empty_trash', 'config.delete_anonim'),
        '#field_suffix'         => $this->basket->Translate()->t('days'),
        '#wrapper_attributes'   => ['class' => ['auto_width']],
      ],
    ];
    $form['actions'] = [
      '#type'         => 'actions',
      'submit'        => [
        '#type'         => 'submit',
        '#value'        => $this->basket->Translate()->t('Save'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->basket->setSettings('empty_trash', 'config', $form_state->getValue('config'));
  }

}
