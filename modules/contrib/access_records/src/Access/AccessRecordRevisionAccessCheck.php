<?php

namespace Drupal\access_records\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\access_records\AccessRecordInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides an access checker for access record revisions.
 *
 * @ingroup access_records_access
 */
class AccessRecordRevisionAccessCheck implements AccessInterface {

  /**
   * The access record storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $accessRecordStorage;

  /**
   * The access record access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessRecordAccess;

  /**
   * A static cache of access checks.
   *
   * @var array
   */
  protected $access = [];

  /**
   * Constructs a new AccessRecordRevisionAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->accessRecordStorage = $entity_type_manager->getStorage('access_record');
    $this->accessRecordAccess = $entity_type_manager->getAccessControlHandler('access_record');
  }

  /**
   * Checks routing access for the access record item revision.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param int $access_record_revision
   *   (Optional) The item revision ID. If not specified, but $access_record is,
   *   access is checked for that object's revision.
   * @param \Drupal\access_records\AccessRecordInterface $access_record
   *   (Optional) An access record item. Used for checking access to an item's
   *   default revision when $access_record_revision is unspecified. Ignored
   *   when $access_record_revision is specified. If neither
   *   $access_record_revision nor $access_record are specified, then access is
   *   denied.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, $access_record_revision = NULL, AccessRecordInterface $access_record = NULL) {
    if ($access_record_revision) {
      $access_record = $this->accessRecordStorage->loadRevision($access_record_revision);
    }
    $operation = $route->getRequirement('_access_access_record_revision');
    return AccessResult::allowedIf($access_record && $this->checkAccess($access_record, $account, $operation))->cachePerPermissions()->addCacheableDependency($access_record);
  }

  /**
   * Checks access record item revision access.
   *
   * @param \Drupal\access_records\AccessRecordInterface $access_record
   *   The access record item to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object representing the user for whom the operation is to be
   *   performed.
   * @param string $op
   *   (optional) The specific operation being checked. Defaults to 'view'.
   *
   * @return bool
   *   TRUE if the operation may be performed, FALSE otherwise.
   */
  public function checkAccess(AccessRecordInterface $access_record, AccountInterface $account, $op = 'view') {
    $map = [
      'view' => 'view access_record revisions',
      'update' => 'revert access_record revisions',
      'delete' => 'delete access_record revisions',
    ];
    $type_id = $access_record->bundle();
    $config_map = [
      'view' => "view $type_id access_record revisions",
      'update' => "revert $type_id access_record revisions",
      'delete' => "delete $type_id access_record revisions",
    ];

    if (!$access_record || !isset($map[$op]) || !isset($config_map[$op])) {
      // If there was no access record to check against, or the $op was not one
      // of the supported ones, we return access denied.
      return FALSE;
    }

    // Statically cache access by revision ID, language code, user account ID,
    // and operation.
    $langcode = $access_record->language()->getId();
    $cid = $access_record->getRevisionId() . ':' . $langcode . ':' . $account->id() . ':' . $op;

    if (!isset($this->access[$cid])) {
      // Perform basic permission checks first.
      if (!$account->hasPermission($map[$op]) && !$account->hasPermission($config_map[$op]) && !$account->hasPermission('administer access_record')) {
        $this->access[$cid] = FALSE;
        return FALSE;
      }
      // If the revisions checkbox is selected for the access record config,
      // display the revisions tab.
      /** @var \Drupal\access_records\AccessRecordTypeInterface $access_record_type */
      $access_record_type = \Drupal::entityTypeManager()->getStorage('access_record_type')->load($type_id);
      if ($access_record_type->shouldCreateNewRevision() && $op === 'view') {
        $this->access[$cid] = TRUE;
      }
      else {
        // There should be at least two revisions. If the revision ID of the
        // given access record and the revision ID of the default revision
        // differ, then we already have different revisions, so there is no need
        // for a separate database check. Also, if you try to revert to or
        // delete the default revision, that's not good.
        if ($access_record->isDefaultRevision() && ($op === 'update' || $op === 'delete' || $this->countDefaultLanguageRevisions($access_record) == 1)) {
          $this->access[$cid] = FALSE;
        }
        elseif ($account->hasPermission('administer access_record')) {
          $this->access[$cid] = TRUE;
        }
        else {
          // First check the access to the default revision and finally, if the
          // access record passed in is not the default revision then check
          // access to that, too.
          $this->access[$cid] = $this->accessRecordAccess->access($this->accessRecordStorage->load($access_record->id()), $op, $account) && ($access_record->isDefaultRevision() || $this->accessRecordAccess->access($access_record, $op, $account));
        }
      }
    }

    return $this->access[$cid];
  }

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\access_records\AccessRecordInterface $access_record
   *   The access record item for which to count the revisions.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  protected function countDefaultLanguageRevisions(AccessRecordInterface $access_record) {
    $entity_type = $access_record->getEntityType();
    $count = $this->accessRecordStorage->getQuery()
      ->accessCheck(FALSE)
      ->allRevisions()
      ->condition($entity_type->getKey('id'), $access_record->id())
      ->count();
    if ($entity_type->isTranslatable()) {
      $count->condition($entity_type->getKey('default_langcode'), 1);
    }
    return $count->accessCheck(FALSE)->execute();
  }

}
