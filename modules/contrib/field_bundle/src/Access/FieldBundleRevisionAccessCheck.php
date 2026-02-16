<?php

namespace Drupal\field_bundle\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field_bundle\FieldBundleInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides an access checker for field bundle revisions.
 *
 * @ingroup field_bundle_access
 */
class FieldBundleRevisionAccessCheck implements AccessInterface {

  /**
   * The field bundle storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $fieldBundleStorage;

  /**
   * The field bundle access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $fieldBundleAccess;

  /**
   * A static cache of access checks.
   *
   * @var array
   */
  protected $access = [];

  /**
   * Constructs a new FieldBundleRevisionAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->fieldBundleStorage = $entity_type_manager->getStorage('field_bundle');
    $this->fieldBundleAccess = $entity_type_manager->getAccessControlHandler('field_bundle');
  }

  /**
   * Checks routing access for the field bundle item revision.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param int $field_bundle_revision
   *   (Optional) The item revision ID. If not specified, but $field_bundle is,
   *   access is checked for that object's revision.
   * @param \Drupal\field_bundle\FieldBundleInterface $field_bundle
   *   (Optional) A field bundle item. Used for checking access to an item's
   *   default revision when $field_bundle_revision is unspecified. Ignored when
   *   $field_bundle_revision is specified. If neither $field_bundle_revision
   *   nor $field_bundle are specified, then access is denied.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, $field_bundle_revision = NULL, FieldBundleInterface $field_bundle = NULL) {
    if ($field_bundle_revision) {
      $field_bundle = $this->fieldBundleStorage->loadRevision($field_bundle_revision);
    }
    $operation = $route->getRequirement('_access_field_bundle_revision');
    return AccessResult::allowedIf($field_bundle && $this->checkAccess($field_bundle, $account, $operation))->cachePerPermissions()->addCacheableDependency($field_bundle);
  }

  /**
   * Checks field bundle item revision access.
   *
   * @param \Drupal\field_bundle\FieldBundleInterface $field_bundle
   *   The field bundle item to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object representing the user for whom the operation is to be
   *   performed.
   * @param string $op
   *   (optional) The specific operation being checked. Defaults to 'view'.
   *
   * @return bool
   *   TRUE if the operation may be performed, FALSE otherwise.
   */
  public function checkAccess(FieldBundleInterface $field_bundle, AccountInterface $account, $op = 'view') {
    $map = [
      'view' => 'view field_bundle revisions',
      'update' => 'revert field_bundle revisions',
      'delete' => 'delete field_bundle revisions',
    ];
    $config_id = $field_bundle->bundle();
    $config_map = [
      'view' => "view $config_id field_bundle revisions",
      'update' => "revert $config_id field_bundle revisions",
      'delete' => "delete $config_id field_bundle revisions",
    ];

    if (!$field_bundle || !isset($map[$op]) || !isset($config_map[$op])) {
      // If there was no field bundle to check against, or the $op was not one
      // of the supported ones, we return access denied.
      return FALSE;
    }

    // Statically cache access by revision ID, language code, user account ID,
    // and operation.
    $langcode = $field_bundle->language()->getId();
    $cid = $field_bundle->getRevisionId() . ':' . $langcode . ':' . $account->id() . ':' . $op;

    if (!isset($this->access[$cid])) {
      // Perform basic permission checks first.
      if (!$account->hasPermission($map[$op]) && !$account->hasPermission($config_map[$op]) && !$account->hasPermission('administer field_bundle')) {
        $this->access[$cid] = FALSE;
        return FALSE;
      }
      // If the revisions checkbox is selected for the field bundle config,
      // display the revisions tab.
      /** @var \Drupal\field_bundle\FieldBundleConfigInterface $field_bundle_config */
      $field_bundle_config = \Drupal::entityTypeManager()->getStorage('field_bundle_config')->load($config_id);
      if ($field_bundle_config->shouldCreateNewRevision() && $op === 'view') {
        $this->access[$cid] = TRUE;
      }
      else {
        // There should be at least two revisions. If the revision ID of the
        // given field bundle and the revision ID of the default revision
        // differ, then we already have different revisions, so there is no need
        // for a separate database check. Also, if you try to revert to or
        // delete the default revision, that's not good.
        if ($field_bundle->isDefaultRevision() && ($op === 'update' || $op === 'delete' || $this->countDefaultLanguageRevisions($field_bundle) == 1)) {
          $this->access[$cid] = FALSE;
        }
        elseif ($account->hasPermission('administer field_bundle')) {
          $this->access[$cid] = TRUE;
        }
        else {
          // First check the access to the default revision and finally, if the
          // field bundle passed in is not the default revision then check
          // access to that, too.
          $this->access[$cid] = $this->fieldBundleAccess->access($this->fieldBundleStorage->load($field_bundle->id()), $op, $account) && ($field_bundle->isDefaultRevision() || $this->fieldBundleAccess->access($field_bundle, $op, $account));
        }
      }
    }

    return $this->access[$cid];
  }

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\field_bundle\FieldBundleInterface $field_bundle
   *   The field bundle item for which to count the revisions.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  protected function countDefaultLanguageRevisions(FieldBundleInterface $field_bundle) {
    $entity_type = $field_bundle->getEntityType();
    $count = $this->fieldBundleStorage->getQuery()
      ->accessCheck(FALSE)
      ->allRevisions()
      ->condition($entity_type->getKey('id'), $field_bundle->id())
      ->condition($entity_type->getKey('default_langcode'), 1)
      ->count()
      ->execute();
    return $count;
  }

}
