<?php

namespace Drupal\commerce_invoice\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines an access checker for the Order invoices route.
 */
class InvoiceOrderAccessCheck implements AccessInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new InvoiceOrderAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access to the Order invoices route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $route_match->getParameter('commerce_order');
    $access = AccessResult::allowedIfHasPermission($account, 'administer commerce_invoice')
      ->mergeCacheMaxAge(0);

    // Custom requirement for the invoice generate form.
    if ($route->hasRequirement('_invoice_generate_form_access')) {
      if (in_array($order->getState()->getId(), ['canceled', 'draft'])) {
        return AccessResult::forbidden()->mergeCacheMaxAge(0);
      }

      $invoice_storage = $this->entityTypeManager->getStorage('commerce_invoice');
      $invoice_ids = $invoice_storage->getQuery()
        ->condition('state', 'canceled', '!=')
        ->condition('orders', [$order->id()], 'IN')
        ->accessCheck(FALSE)
        ->execute();

      // Do not allow access to the invoice generate form if this order is already
      // referenced by an invoice.
      if ($invoice_ids) {
        return AccessResult::forbidden()->mergeCacheMaxAge(0);
      }

      // The invoice generator service needs a store and a billing profile.
      $order_requirements = !empty($order->getStoreId()) && !empty($order->getBillingProfile());
      $access->andIf(AccessResult::allowedIf($order_requirements));
    }

    return $access;
  }

}
