<?php

namespace Drupal\storage\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\storage\Entity\StorageInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides an access checker for storage revisions.
 *
 * @ingroup storage_access
 */
class StorageRevisionAccessCheck implements AccessInterface {

  /**
   * The storage storage.
   *
   * @var \Drupal\storage\StorageStorageInterface
   */
  protected $storageStorage;

  /**
   * The storage access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $storageAccess;

  /**
   * A static cache of access checks.
   *
   * @var array
   */
  protected $access = [];

  /**
   * Constructs a new StorageRevisionAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->storageStorage = $entity_type_manager->getStorage('storage');
    $this->storageAccess = $entity_type_manager->getAccessControlHandler('storage');
  }

  /**
   * Checks routing access for the storage revision.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param int $storage_revision
   *   (optional) The storage revision ID. If not specified, but $storage is,
   *   access is checked for that object's revision.
   * @param \Drupal\storage\StorageInterface $storage
   *   (optional) A storage object. Used for checking access to a storage's
   *   default revision when $storage_revision is unspecified. Ignored when
   *   $storage_revision is specified. If neither $storage_revision nor
   *   $storage are specified, then access is denied.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, $storage_revision = NULL, StorageInterface $storage = NULL) {
    if ($storage_revision) {
      $storage = $this->storageStorage->loadRevision($storage_revision);
    }
    $operation = $route->getRequirement('_access_storage_revision');
    return AccessResult::allowedIf($storage && $this->checkAccess($storage, $account, $operation))->cachePerPermissions()->addCacheableDependency($storage);
  }

  /**
   * Checks storage revision access.
   *
   * @param \Drupal\storage\StorageInterface $storage
   *   The storage to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object representing the user for whom the operation is to be
   *   performed.
   * @param string $op
   *   (Optional) The specific operation being checked. Defaults to 'view'.
   *
   * @return bool
   *   TRUE if the operation may be performed, FALSE otherwise.
   */
  public function checkAccess(StorageInterface $storage, AccountInterface $account, $op = 'view') {
    $map = [
      'view' => 'view all storage revisions',
      'update' => 'revert all storage revisions',
      'delete' => 'delete all storage revisions',
    ];
    $bundle = $storage->bundle();
    $type_map = [
      'view' => "view $bundle storage revisions",
      'update' => "revert $bundle storage revisions",
      'delete' => "delete $bundle storage revisions",
    ];

    if (!$storage || !isset($map[$op]) || !isset($type_map[$op])) {
      // If there was no storage to check against, or the $op was not one of the
      // supported ones, we return access denied.
      return FALSE;
    }

    // Statically cache access by revision ID, language code, user account ID,
    // and operation.
    $langcode = $storage->language()->getId();
    $cid = $storage->getRevisionId() . ':' . $langcode . ':' . $account->id() . ':' . $op;

    if (!isset($this->access[$cid])) {
      // Perform basic permission checks first.
      if (!$account->hasPermission($map[$op]) && !$account->hasPermission($type_map[$op]) && !$account->hasPermission('administer storage entities')) {
        $this->access[$cid] = FALSE;
        return FALSE;
      }
      // If the revisions checkbox is selected for the storage type, display the
      // revisions tab.
      $bundle_entity_type = $storage->getEntityType()->getBundleEntityType();
      $bundle_entity = \Drupal::entityTypeManager()->getStorage($bundle_entity_type)->load($bundle);
      if ($bundle_entity->shouldCreateNewRevision() && $op === 'view') {
        $this->access[$cid] = TRUE;
      }
      else {
        // There should be at least two revisions. If the vid of the given
        // storage and the vid of the default revision differ, then we already
        // have two different revisions so there is no need for a separate
        // database check. Also, if you try to revert to or delete the default
        // revision, that's not good.
        if ($storage->isDefaultRevision() && ($this->storageStorage->countDefaultLanguageRevisions($storage) == 1 || $op === 'update' || $op === 'delete')) {
          $this->access[$cid] = FALSE;
        }
        elseif ($account->hasPermission('administer storage entities')) {
          $this->access[$cid] = TRUE;
        }
        else {
          // First check the access to the default revision and finally, if the
          // storage passed in is not the default revision then check access to
          // that, too.
          $this->access[$cid] = $this->storageAccess->access($this->storageStorage->load($storage->id()), $op, $account) && ($storage->isDefaultRevision() || $this->storageAccess->access($storage, $op, $account));
        }
      }
    }

    return $this->access[$cid];
  }

}
