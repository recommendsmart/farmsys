<?php

namespace Drupal\basket\Plugin\Basket\Delivery;

use Drupal\basket\Plugins\Delivery\BasketDeliveryInterface;

/**
 * Plugin for delivery address field.
 *
 * @BasketDelivery(
 *  id        = "basket_address_field",
 *  name      = "Delivery address",
 * )
 */
class DeliveryAddressField implements BasketDeliveryInterface {

  const FIELDS = 'basket_address_field';

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
   * Set address.
   *
   * @var array
   */
  protected $address;

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
  public function basketFieldParents() {
    return [self::FIELDS];
  }

  /**
   * {@inheritdoc}
   */
  public function basketFormAlter(&$form, $form_state) {
    $tid = $form_state->getValue(['basket_delivery', 'value']);
    $settings = $this->basket->getSettings('delivery_settings', $tid);

    $form['address'] = [
      '#type'            => 'textarea',
      '#required'        => !empty($settings['required']),
      '#title'        => !empty($settings['title']) ? $this->trans->trans(trim($settings['title'])) : NULL,
      '#title_display' => !empty($settings['title_display']) ? 'before' : 'none',
      '#default_value' => $this->basketLoad($form_state),
    ];
    if (!empty($settings['placeholder'])) {
      $form['address']['#attributes']['placeholder'] = $this->trans->trans(trim($settings['placeholder']));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function basketDelete($entity, $entity_delete) {}

  /**
   * {@inheritdoc}
   */
  public function basketSave($entity, $form_state) {
    $this->address[$entity->id()] = $form_state->getValue([
      self::FIELDS,
      'address',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function basketLoad($form_state) {
    $getAddress = $form_state->getValue([self::FIELDS, 'address']);
    if (empty($getAddress)) {
      $entity = $form_state->getBuildInfo()['callback_object']->getEntity();
      $getAddress = \Drupal::database()->select('basket_orders_delivery', 'd')
        ->fields('d', ['address'])
        ->condition('d.did', $form_state->getValue(['basket_delivery', 'value']))
        ->condition('d.nid', $entity->id())
        ->execute()->fetchField();
      $getAddress = !empty($getAddress) ? unserialize($getAddress) : NULL;
    }
    return $getAddress;
  }

  /**
   * {@inheritdoc}
   */
  public function basketGetAddress($entity) {
    return !empty($this->address[$entity->id()]) ? $this->address[$entity->id()] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function deliverySumAlter(&$info) {}

}
