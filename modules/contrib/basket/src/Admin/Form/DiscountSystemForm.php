<?php

namespace Drupal\basket\Admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class DiscountSystemForm extends FormBase {

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
    return 'basket_discount_system_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['enabled'] = [
      '#type'         => 'checkbox',
      '#title'        => $this->basket->Translate()->t('Enable service'),
      '#default_value' => $this->basket->getSettings('enabled_services', 'discount_system'),
    ];
    $form['config_wrap'] = [
      '#type'         => 'container',
      '#states'       => [
        'visible'       => [
          'input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
      'description'   => [
        '#markup'       => '<div class="description">' . $this->basket->Translate()->t('The maximum discount from the presented systems is taken into account.') . '</div>',
      ],
      'config'        => [
        '#type'         => 'table',
        '#header'       => [
          '',
          $this->basket->Translate()->t('Service'),
          $this->basket->Translate()->t('Settings'),
        ],
        '#empty'        => $this->basket->Translate()->t('The list is empty.'),
      ],
    ];
    $dasketDiscounts = \Drupal::service('BasketDiscount')->getDefinitions();
    if (!empty($dasketDiscounts)) {
      foreach ($dasketDiscounts as $dasketDiscount) {
        $form['config_wrap']['config'][$dasketDiscount['id']] = [
          'active'        => [
            '#type'         => 'checkbox',
            '#title'        => $dasketDiscount['id'],
            '#attributes'   => [
              'class'         => ['not_label'],
            ],
            '#default_value' => $this->basket->getSettings('discount_system', 'config.' . $dasketDiscount['id'] . '.active'),
          ],
          'name'          => [
            '#markup'       => $this->basket->Translate($dasketDiscount['provider'])->trans(trim($dasketDiscount['name'])),
          ],
          'settings'      => \Drupal::service('BasketDiscount')->getInstanceByID($dasketDiscount['id'])->settingsLink(),
        ];
      }
    }
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
    // enabled_services.
    $this->basket->setSettings(
      'enabled_services',
      'discount_system',
      $form_state->getValue('enabled')
    );
    $this->basket->setSettings('discount_system', 'config', $form_state->getValue('config'));
  }

}
