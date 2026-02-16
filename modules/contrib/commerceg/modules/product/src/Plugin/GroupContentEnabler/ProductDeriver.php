<?php

namespace Drupal\commerceg_product\Plugin\GroupContentEnabler;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides per product bundle definitions of the group content enabler plugin.
 */
class ProductDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The product type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $productTypeStorage;

  /**
   * Constructs a new ProductDeriver object.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $product_type_storage
   *   The product type storage.
   */
  public function __construct(
    ConfigEntityStorageInterface $product_type_storage
  ) {
    $this->productTypeStorage = $product_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    $base_plugin_id
  ) {
    $product_type_storage = $container
      ->get('entity_type.manager')
      ->getStorage('commerce_product_type');

    return new static($product_type_storage);
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $product_types = $this->productTypeStorage->loadMultiple();

    foreach ($product_types as $name => $product_type) {
      $label = $product_type->label();

      $this->derivatives[$name] = [
        'entity_bundle' => $name,
        'label' => t('Group product (@type)', ['@type' => $label]),
        'description' => t(
          'Adds %type products to groups.',
          ['%type' => $label]
        ),
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
