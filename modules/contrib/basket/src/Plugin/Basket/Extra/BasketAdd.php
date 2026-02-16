<?php

namespace Drupal\basket\Plugin\Basket\Extra;

use Drupal\basket\Plugins\Extra\BasketExtraSettingsInterface;
use Drupal\basket\Plugins\Extra\Annotation\BasketExtraSettings;

/**
 * @BasketExtraSettings(
 *          id        = "basket_add",
 *          name      = "Basket Add",
 * )
 */
class BasketAdd implements BasketExtraSettingsInterface {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set trans.
   *
   * @var Drupal\basket\BasketTranslate
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
   * Gets extra field settings form.
   *
   * @param string $field_name
   *   The extra field machine name.
   *
   * @return array
   *   Array with form fields or empty array.
   */
  public function getSettingsForm() {
    $form = [];
    $form['text'] = [
      '#type'         => 'textfield',
      '#title'        => $this->trans->t('Button text'),
    ];
    $form['count'] = [
      '#type'         => 'checkbox',
      '#title'        => $this->trans->t('Show + / -'),
    ];
    return $form;
  }

  /**
   * Gets extra field settings summary.
   *
   * @return string
   */
  public function getSettingsSummary($settings) {
    return implode('<br/>', [
      $this->trans->t('Button text').': '.($settings['text'] ?? ''),
      $this->trans->t('Show + / -').': '.(!empty($settings['count']) ? t('yes') : t('no'))
    ]);
  }
}
