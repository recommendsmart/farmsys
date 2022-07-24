<?php

namespace Drupal\basket\Admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * {@inheritdoc}
 */
class SettingsOrderForm extends FormBase {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set phoneOptions.
   *
   * @var array
   */
  protected $phoneOptions;

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
    return 'basket_order_form_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = '', $tid = NULL) {
    $form['config'] = ['#tree' => TRUE];
    // submit_button.
    $form['config']['submit_button'] = [
      '#type'         => 'textfield',
      '#title'        => $this->basket->Translate()->trans('Button text') . ':',
      '#default_value' => $this->basket->getSettings('order_form', 'config.submit_button'),
    ];
    // submit_redirect.
    $form['config']['submit_redirect'] = [
      '#type'         => 'select',
      '#title'        => $this->basket->Translate()->trans('Redirect after ordering') . ':',
      '#options'      => [
        '<front>'       => '\\',
        'finish'        => '\basket\finish',
        'reload'        => $this->basket->Translate()->t('Reload page'),
      ],
      '#default_value' => $this->basket->getSettings('order_form', 'config.submit_redirect'),
    ];
    $form['config']['submit_finish_template'] = [
      '#type'         => 'container',
      '#states'       => [
        'visible'       => [
          'select[name="config[submit_redirect]"]'    => ['value' => 'finish'],
        ],
      ],
      [
        '#type'         => 'link',
        '#title'        => $this->basket->Translate()->t('Customize the template'),
        '#url'          => new Url('basket.admin.pages', ['page_type' => 'settings-templates-basket_finish'], [
          'attributes'    => [
            'class'         => ['target_link'],
            'target'        => '_blank',
          ],
        ]),
      ],
    ];
    // submit_message.
    $form['config']['submit_message'] = [
      '#type'         => 'textarea',
      '#title'        => $this->basket->Translate()->trans('Message after order') . ':',
      '#rows'         => 1,
      '#default_value' => $this->basket->getSettings('order_form', 'config.submit_message'),
    ];
    // default_values.
    $form['config']['default_values'] = [
      '#type'         => 'details',
      '#title'        => $this->basket->Translate()->t('Default form data'),
      'tokens'        => [
        '#theme'        => 'token_tree_link',
        '#token_types'  => ['user'],
        '#text'         => $this->basket->Translate()->t('[available tokens]'),
      ],
    ];
    foreach (\Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'basket_order') as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
        switch ($field_name) {
          case'uid':
          case'title':
          case'changed':
          case'sticky':
          case'path':
          case'menu_link':
          case'created':
          case'status':
          case'promote':
            break;

          default:
            $form['config']['default_values'][$field_name] = [
              '#type'         => 'textfield',
              '#title'        => $field_definition->getLabel(),
              '#default_value' => $this->basket->getSettings('order_form', 'config.default_values.' . $field_name),
            ];
            $this->phoneOptions[$field_name] = $field_definition->getLabel();
            break;
        }
      }
    }
    // Phone mask.
    $form['config']['phone_mask'] = [
      '#type'         => 'details',
      '#title'        => $this->basket->Translate()->t('Phone mask'),
      '#open'         => TRUE,
      'field'         => [
        '#type'         => 'select',
        '#title'        => $this->basket->Translate()->t('Phone field'),
        '#options'      => $this->phoneOptions,
        '#empty_value'  => '',
        '#default_value' => $this->basket->getSettings('order_form', 'config.phone_mask.field'),
      ],
      'mask'          => [
        '#type'         => 'textfield',
        '#title'        => $this->basket->Translate()->t('Mask'),
        '#states'       => [
          'visible'       => [
            'select[name="config[phone_mask][field]"]'      => ['!value' => ''],
          ],
        ],
        '#default_value' => $this->basket->getSettings('order_form', 'config.phone_mask.mask'),
      ],
    ];
    $options = [];
    foreach (\Drupal::service('entity_display.repository')->getFormModes('node') as $modeKey => $mode) {
      $options[$modeKey] = $mode['label'];
    }
    $form['config']['form_mode'] = [
      '#type'         => 'select',
      '#title'        => t('Form mode'),
      '#options'      => $options,
      '#empty_option' => t('Default'),
      '#default_value' => $this->basket->getSettings('order_form', 'config.form_mode')
    ];
    // ---
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
    $this->basket->setSettings('order_form', 'config', $form_state->getValue('config'));
  }

}
