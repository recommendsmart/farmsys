<?php

namespace Drupal\commerce_funds;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_product\Entity\ProductVariation;

/**
 * Product manager interface.
 */
interface ProductManagerInterface {

  /**
   * Create product and its variations.
   *
   * @param string $type
   *   The type of the Product (deposit or fees).
   * @param float $amount
   *   The amount of the product type.
   * @param string $currency_code
   *   The currency code of the product type.
   *
   * @return Drupal\commerce_product\Entity\ProductVariation
   *   The product variation, deposit or fee, of the amount.
   */
  public function createProduct($type, $amount, $currency_code);

  /**
   * Create an order with a product variation.
   *
   * @param Drupal\commerce_product\Entity\ProductVariation $product_variation
   *   The product variation of the amount.
   *
   * @return Drupal\commerce_order\Entity\Order
   *   An order object with the product variation.
   */
  public function createOrder(ProductVariation $product_variation);

  /**
   * Update an existing order with fee product.
   *
   * @param Drupal\commerce_order\Entity\Order $order
   *   The order with a deposit amount product variation.
   * @param Drupal\commerce_product\Entity\ProductVariation $product_variation
   *   The deposit amount product variation.
   *
   * @return Drupal\commerce_order\Entity\Order
   *   The order with the fee product variation added.
   */
  public function updateOrder(Order $order, ProductVariation $product_variation);

}
