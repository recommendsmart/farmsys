<?php

namespace Drupal\basket\Admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * {@inheritdoc}
 */
class SettingsNotificationsForm extends FormBase {

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
    return 'basket_notifications_form_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = '', $tid = NULL) {
    $form['config'] = [
      '#tree'         => TRUE,
      '#type'         => 'table',
    ];
    // notification_order_admin.
    $form['config'][] = [
     [
       '#type'         => 'checkbox',
       '#title'        => $this->basket->Translate()->t('Admin notification of new order'),
       '#default_value' => $this->basket->getSettings('notifications', 'config.notification_order_admin'),
       '#parents'      => ['config', 'notification_order_admin'],
     ], [
       '#type'         => 'textarea',
       '#default_value' => $this->basket->getSettings('notifications', 'config.notification_order_admin_mails'),
       '#parents'      => ['config', 'notification_order_admin_mails'],
       '#states'       => [
         'visible'       => [
           'input[name="config[notification_order_admin]"]' => ['checked' => TRUE],
         ],
       ],
       '#attributes'   => [
         'placeholder'   => $this->basket->Translate()->t('One line - one email'),
       ],
       '#rows'         => 2,
     ], [
       'data'          => $this->setTemplateLink('notification_order_admin'),
     ],
    ];
    // notification_order_user.
    $form['config'][] = [
     [
       '#type'         => 'checkbox',
       '#title'        => $this->basket->Translate()->t('Notification to the user about the created order'),
       '#default_value' => $this->basket->getSettings('notifications', 'config.notification_order_user'),
       '#parents'      => ['config', 'notification_order_user'],
     ], [
       '#type'             => 'select',
       '#options'          => $this->basket->getNodeTypeFields('basket_order', ['email']),
       '#empty_option'    => '',
       '#default_value' => $this->basket->getSettings('notifications', 'config.notification_order_user_field'),
       '#parents'      => ['config', 'notification_order_user_field'],
       '#states'       => [
         'visible'       => [
           'input[name="config[notification_order_user]"]' => ['checked' => TRUE],
         ],
       ],
     ], [
       'data'          => $this->setTemplateLink('notification_order_user'),
     ],
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
    $this->basket->setSettings('notifications', 'config', $form_state->getValue('config'));
  }

  /**
   * {@inheritdoc}
   */
  private function setTemplateLink($type) {
    return [
      '#type'         => 'container',
      '#states'       => [
        'visible'       => [
          'input[name="config[' . $type . ']"]'    => ['checked' => TRUE],
        ],
      ], [
        '#type'         => 'inline_template',
        '#template'     => '<a href="{{url}}" target="_blank"><span class="ico">{{ico|raw}}</span> {{text}}</a>',
        '#context'      => [
          'ico'           => $this->basket->getIco('settings_row.svg'),
          'text'          => $this->basket->Translate()->t('Customize the template'),
          'url'           => new Url('basket.admin.pages', ['page_type' => 'settings-templates-' . $type]),
        ],
      ],
    ];
  }

}
