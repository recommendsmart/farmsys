<?php

namespace Drupal\basket\Admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * {@inheritdoc}
 */
class TermSettingsForm extends FormBase {

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
    return 'basket_term_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = '', $tid = NULL) {
    $term = !empty($tid) ? $this->basket->Term()->load($tid) : NULL;
    $form['#prefix'] = '<div id="basket_term_settings_ajax_wrap">';
    $form['#suffix'] = '</div>';
    $form['status_messages'] = [
      '#type'         => 'status_messages',
    ];
    $form['type'] = [
      '#type'         => 'hidden',
      '#value'        => $type,
    ];
    $form['tid'] = [
      '#type'         => 'hidden',
      '#value'        => !empty($term->id) ? $term->id : NULL,
    ];
    $form['name'] = [
      '#type'            => 'textfield',
      '#title'        => $this->trans->trans('Name') . ' EN',
      '#required'        => TRUE,
      '#default_value' => !empty($term->name) ? $term->name : '',
    ];
    $form['color'] = [
      '#type'            => 'textfield',
      '#title'        => $this->trans->t('Color'),
      '#required'        => TRUE,
      '#attributes'   => [
        'readonly'      => 'readonly',
        'class'         => ['color_input'],
      ],
      '#default_value' => !empty($term->color) ? $term->color : '#ffffff',
    ];
    $form['actions'] = [
      '#type'            => 'actions',
      'submit'        => [
        '#type'            => 'submit',
        '#value'        => $this->trans->t('Save'),
        '#ajax'            => [
          'wrapper'        => 'basket_term_settings_ajax_wrap',
          'callback'        => '::ajaxSubmit',
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state){}

  /**
   * {@inheritdoc}
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    if ($form_state->isSubmitted() && $form_state->getErrors()) {
      return $form;
    }
    else {
      $values = $form_state->getValues();
      if (!empty($values['tid'])) {
        \Drupal::database()->update('basket_terms')
          ->fields([
            'name'      => trim($values['name']),
            'color'     => trim($values['color']),
          ])
          ->condition('id', $values['tid'])
          ->execute();
      }
      else {
        \Drupal::database()->insert('basket_terms')
          ->fields([
            'type'      => $values['type'],
            'name'      => trim($values['name']),
            'color'     => trim($values['color']),
            'weight'    => -100,
          ])
          ->execute();
      }
      $response = new AjaxResponse();
      $response->addCommand(new InvokeCommand('body', 'append', ['<script>location.reload();</script>']));
      return $response;
    }
  }

}
