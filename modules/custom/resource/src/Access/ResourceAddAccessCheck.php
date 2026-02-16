<?php

/**
 * @file
 * Contains \Drupal\resource\Access\ResourceAddAccessCheck.
 */

namespace Drupal\resource\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\resource\ResourceTypeInterface;

/**
 * Determines access to for resource add pages.
 *
 * @ingroup resource_access
 */
class ResourceAddAccessCheck implements AccessInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * Checks access to the resource add page for the resource type.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\resource\ResourceTypeInterface $resource_type
   *   (optional) The resource type. If not specified, access is allowed if there
   *   exists at least one resource type for which the user may create a resource.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(AccountInterface $account, ResourceTypeInterface $resource_type = NULL) {
    $access_control_handler = $this->entityManager->getAccessControlHandler('resource');
    // If checking whether a resource of a particular type may be created.
    if ($account->hasPermission('administer resource types')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    if ($resource_type) {
      return $access_control_handler->createAccess($resource_type->id(), $account, [], TRUE);
    }
    // If checking whether a resource of any type may be created.
    foreach ($this->entityManager->getStorage('resource_type')->loadMultiple() as $resource_type) {
      if (($access = $access_control_handler->createAccess($resource_type->id(), $account, [], TRUE)) && $access->isAllowed()) {
        return $access;
      }
    }
    // No opinion.
    return AccessResult::neutral();
  }

}
