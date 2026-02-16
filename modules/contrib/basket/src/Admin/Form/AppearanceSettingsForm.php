<?php

namespace Drupal\basket\Admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class AppearanceSettingsForm extends FormBase {

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
    return 'appearance_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'basket/colorpicker';
    $form['config'] = [
      '#tree'             => TRUE,
      'temlates'          => [
        '#type'             => 'details',
        '#title'            => $this->basket->Translate()->t('Message template'),
        '#open'             => TRUE,
        'color'             => [
          '#type'             => 'textfield',
          '#title'            => $this->basket->Translate()->t('Background color'),
          '#attributes'       => [
            'readonly'          => 'readonly',
            'class'             => ['color_input'],
          ],
          '#default_value'    => $this->basket->getSettings('appearance', 'config.temlates.color'),
        ],
        'color_text'        => [
          '#type'             => 'textfield',
          '#title'            => $this->basket->Translate()->t('Text color'),
          '#attributes'       => [
            'readonly'          => 'readonly',
            'class'             => ['color_input'],
          ],
          '#default_value'    => $this->basket->getSettings('appearance', 'config.temlates.color_text'),
        ],
        'phone_html'        => [
          '#type'             => 'textarea',
          '#title'            => $this->basket->Translate()->t('Phone HTML'),
          '#default_value'    => $this->basket->getSettings('appearance', 'config.temlates.phone_html'),
          '#rows'             => 2,
        ],
        'work_html'        => [
          '#type'             => 'textarea',
          '#title'            => $this->basket->Translate()->t('Schedule of work HTML'),
          '#default_value'    => $this->basket->getSettings('appearance', 'config.temlates.work_html'),
          '#rows'             => 2,
        ],
        'links'             => [
          'fb'                => [
            '#type'             => 'textfield',
            '#title'            => 'Facebook link',
            '#default_value'    => $this->basket->getSettings('appearance', 'config.temlates.links.fb'),
          ],
          'youtube'           => [
            '#type'             => 'textfield',
            '#title'            => 'Youtube link',
            '#default_value'    => $this->basket->getSettings('appearance', 'config.temlates.links.youtube'),
          ],
          'telegram'           => [
            '#type'             => 'textfield',
            '#title'            => 'Telegram link',
            '#default_value'    => $this->basket->getSettings('appearance', 'config.temlates.links.telegram'),
          ],
          'viber'             => [
            '#type'             => 'textfield',
            '#title'            => 'Viber link',
            '#default_value'    => $this->basket->getSettings('appearance', 'config.temlates.links.viber'),
          ],
          'instagram'         => [
            '#type'             => 'textfield',
            '#title'            => 'Instagram link',
            '#default_value'    => $this->basket->getSettings('appearance', 'config.temlates.links.instagram'),
          ],
        ],
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
    $this->basket->setSettings('appearance', 'config', $form_state->getValue('config'));
  }

}
