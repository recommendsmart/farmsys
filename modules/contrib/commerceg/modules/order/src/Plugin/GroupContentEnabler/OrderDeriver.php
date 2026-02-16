<?php

namespace Drupal\commerceg_order\Plugin\GroupContentEnabler;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides per order bundle definitions of the group content enabler plugin.
 */
class OrderDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The order type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $orderTypeStorage;

  /**
   * Constructs a new OrderDeriver object.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $order_type_storage
   *   The order type storage.
   */
  public function __construct(
    ConfigEntityStorageInterface $order_type_storage
  ) {
    $this->orderTypeStorage = $order_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    $base_plugin_id
  ) {
    $order_type_storage = $container
      ->get('entity_type.manager')
      ->getStorage('commerce_order_type');

    return new static($order_type_storage);
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $order_types = $this->orderTypeStorage->loadMultiple();

    foreach ($order_types as $name => $order_type) {
      $label = $order_type->label();

      $this->derivatives[$name] = [
        'entity_bundle' => $name,
        'label' => t('Group order (@type)', ['@type' => $label]),
        'description' => t(
          'Adds %type orders to groups.',
          ['%type' => $label]
        ),
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
