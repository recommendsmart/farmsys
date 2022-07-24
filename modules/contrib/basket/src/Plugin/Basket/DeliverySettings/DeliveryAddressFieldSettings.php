<?php

namespace Drupal\basket\Plugin\Basket\DeliverySettings;

use Drupal\basket\Plugins\DeliverySettings\BasketDeliverySettingsInterface;

/**
 * Delivery address field settings plan.
 *
 * @BasketDeliverySettings(
 *  id              = "basket_address_field_settings",
 *  name            = "DeliverySettings address",
 *  parent_field    = "basket_address_field"
 * )
 */
class DeliveryAddressFieldSettings implements BasketDeliverySettingsInterface {

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
  public function settingsFormAlter(&$form, $form_state) {
    $tid = $form['tid']['#value'];
    $form['settings'] = [
      '#type'         => 'details',
      '#title'        => $this->trans->t('Settings'),
      '#open'         => TRUE,
      '#parents'      => ['basket_address_field_settings'],
      '#tree'         => TRUE,
      'required'      => [
        '#type'         => 'checkbox',
        '#title'        => $this->trans->t('Required field'),
        '#default_value' => $this->basket->getSettings('delivery_settings', $tid . '.required'),
      ],
      'title'         => [
        '#type'         => 'textfield',
        '#title'        => implode(' ', [$this->trans->t('Title'), 'EN:']),
        '#default_value' => $this->basket->getSettings('delivery_settings', $tid . '.title'),
      ],
      'title_display' => [
        '#type'         => 'checkbox',
        '#title'        => $this->trans->t('Display title'),
        '#default_value' => $this->basket->getSettings('delivery_settings', $tid . '.title_display'),
      ],
      'placeholder'   => [
        '#type'         => 'textfield',
        '#title'        => implode('', [$this->trans->t('Placeholder'), ':']),
        '#default_value' => $this->basket->getSettings('delivery_settings', $tid . '.placeholder'),
      ],
    ];
    $form['#submit'][] = __CLASS__ . '::formSubmit';
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsInfoList($tid) {
    $items = [];
    if (!empty($settings = $this->basket->getSettings('delivery_settings', $tid))) {
      $items[] = [
        '#type'         => 'inline_template',
        '#template'     => '<b>{{ label }}: </b> {{ value }}',
        '#context'      => [
          'label'           => $this->trans->t('Required field'),
          'value'         => !empty($settings['required']) ? $this->trans->t('yes') : $this->trans->t('no'),
        ],
      ];
      $items[] = [
        '#type'         => 'inline_template',
        '#template'     => '<b>{{ label }}: </b> {{ value }} {{ translate }}',
        '#context'      => [
          'label'           => $this->trans->t('Title'),
          'value'         => !empty($settings['title']) ? $this->trans->trans(trim($settings['title'])) : NULL,
          'translate'     => !empty($settings['title']) ? $this->trans->getTranslateLink(trim($settings['title'])) : NULL,
        ],
      ];
      $items[] = [
        '#type'         => 'inline_template',
        '#template'     => '<b>{{ label }}: </b> {{ value }} {{ translate }}',
        '#context'      => [
          'label'           => $this->trans->t('Display title'),
          'value'         => !empty($settings['title_display']) ? $this->trans->t('yes') : $this->trans->t('no'),
        ],
      ];
      $items[] = [
        '#type'         => 'inline_template',
        '#template'     => '<b>{{ label }}: </b> {{ value }} {{ translate }}',
        '#context'      => [
          'label'           => $this->trans->t('Placeholder'),
          'value'         => !empty($settings['placeholder']) ? $this->trans->trans(trim($settings['placeholder'])) : NULL,
          'translate'     => !empty($settings['placeholder']) ? $this->trans->getTranslateLink(trim($settings['placeholder'])) : NULL,
        ],
      ];
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public static function formSubmit($form, $form_state) {
    $tid = $form_state->getValue('tid');
    if (!empty($tid)) {
      \Drupal::service('Basket')->setSettings('delivery_settings', $tid, $form_state->getValue('basket_address_field_settings'));
    }
  }

}
