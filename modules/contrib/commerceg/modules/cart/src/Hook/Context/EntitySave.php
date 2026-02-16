<?php

namespace Drupal\commerceg_cart\Hook\Context;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerceg_context\Context\ManagerInterface as ContextManagerInterface;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Holds methods implementing hooks related to entity saving.
 *
 * This service is defined only when the Context submodule is enabled; we don't
 * take any action when carts are saved otherwise.
 */
class EntitySave {

  /**
   * The shopping context manager.
   *
   * @var \Drupal\commerceg_context\Context\ManagerInterface
   */
  protected $contextManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The context module settings configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a new EntitySave object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user account proxy.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\commerceg_context\Context\ManagerInterface $context_manager
   *   The shopping context manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    AccountProxyInterface $account,
    ConfigFactoryInterface $config_factory,
    ContextManagerInterface $context_manager,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->account = $account->getAccount();
    $this->config = $config_factory->get('commerceg_context.settings');
    $this->contextManager = $context_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Implements hook_ENTITY_TYPE_insert().
   *
   * We add new cart orders to the user's current context, if any.
   *
   * Orders can be created in different circumstances outside of the process of
   * adding products to the cart. For example, a store manager can create an
   * order on behalf of another user. We therefore take the following
   * precautions.
   * - We do nothing if the order is not a cart.
   * - We do nothing if the current user is not the order's customer.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order that was created.
   *
   * @I Review default UI for creating orders
   *    type     : improvement
   *    priority : normal
   *    labels   : cart, context, ux
   *    notes    : Allow selecting a context when creating an order, making
   *               available the contexts of the order's customer.
   */
  public function commerceOrderInsert(OrderInterface $order) {
    if (!$this->config->get('status')) {
      return;
    }

    if ($this->account->id() != $order->getCustomerId()) {
      return;
    }
    if (!$order->get('cart')) {
      return;
    }

    $context = $this->contextManager->get();
    if (!$context) {
      return;
    }

    $content_storage = $this->entityTypeManager->getStorage('group_content');
    $content = $content_storage->createForEntityInGroup(
      $order,
      $context,
      'commerceg_order:' . $order->bundle()
    );
    $content_storage->save($content);
  }

}
