<?php

namespace Drupal\subgroup\Access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\CalculatedGroupPermissionsItem;
use Drupal\group\Access\CalculatedGroupPermissionsItemInterface;
use Drupal\group\Access\GroupPermissionCalculatorBase;
use Drupal\group\Access\RefinableCalculatedGroupPermissions;
use Drupal\group\GroupMembershipLoaderInterface;

/**
 * Calculates inherited group permissions for an account.
 */
class InheritedGroupPermissionCalculator extends GroupPermissionCalculatorBase {

  /**
   * Cache the calculated member permissions per user.
   *
   * The member roles depend on which memberships you have, for which we do not
   * currently have a dedicated cache context as it has a very high granularity.
   */
  const MEMBER_CACHE_CONTEXTS = ['user'];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The membership loader service.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $membershipLoader;

  /**
   * Constructs a InheritedGroupPermissionCalculator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\GroupMembershipLoaderInterface $membership_loader
   *   The group membership loader service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GroupMembershipLoaderInterface $membership_loader) {
    $this->entityTypeManager = $entity_type_manager;
    $this->membershipLoader = $membership_loader;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateMemberPermissions(AccountInterface $account) {
    $calculated_permissions = new RefinableCalculatedGroupPermissions();

    // The inherited permissions need to be recalculated whenever the user is
    // added to or removed from a group.
    $calculated_permissions->addCacheTags(['group_content_list:plugin:group_membership:entity:' . $account->id()]);

    /** @var \Drupal\group\Entity\GroupTypeInterface[] $group_types */
    $group_types = $this->entityTypeManager->getStorage('group_type')->loadMultiple();

    /** @var \Drupal\subgroup\Entity\SubgroupHandlerInterface $group_handler */
    $group_handler = $this->entityTypeManager->getHandler('group', 'subgroup');

    /** @var \Drupal\subgroup\Entity\SubgroupHandlerInterface $group_type_handler */
    $group_type_handler = $this->entityTypeManager->getHandler('group_type', 'subgroup');

    /** @var \Drupal\subgroup\Entity\RoleInheritanceStorageInterface $inheritance_storage */
    $inheritance_storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');

    // Performance boost: Keep track of which tree a group type belongs to.
    $tree_per_group_type = [];

    $group_storage = $this->entityTypeManager->getStorage('group');
    foreach ($this->membershipLoader->loadByUser($account) as $group_membership) {
      $group = $group_membership->getGroup();
      $group_type_id = $group->bundle();

      // If the group is not a leaf, there can be no inheritance.
      if (!$group_handler->isLeaf($group)) {
        // Groups of the root level can becomes leaves, meaning a group which is
        // not a leaf now could be updated to become one. So we need to add the
        // group's cacheable metadata to reflect this.
        $calculated_permissions->addCacheableDependency($group);
        continue;
      }

      // Wrap the leaf and figure out what tree its group type belongs to.
      $leaf = $group_handler->wrapLeaf($group);
      if (!isset($tree_per_group_type[$group_type_id])) {
        $tree_per_group_type[$group_type_id] = $group_type_handler->wrapLeaf($group_types[$group_type_id])->getTree();
      }

      // From this point on, any changes in the membership's roles might change
      // what we calculate here. So add the membership as a dependency.
      $calculated_permissions->addCacheableDependency($group_membership);

      // To speed things up, we get the role IDs directly rather than call the
      // getRoles() method on the membership. This is because we do not wish
      // to load the roles but only get the role IDs.
      $role_ids = [$group_types[$group_type_id]->getMemberRoleId()];
      foreach ($group_membership->getGroupContent()->group_roles as $group_role_ref) {
        $role_ids[] = $group_role_ref->target_id;
      }

      // The inherited permissions need to be recalculated whenever a new role
      // inheritance is set up for this tree or one is removed from it.
      $calculated_permissions->addCacheTags([
        'subgroup_role_inheritance_list:tree:' . $tree_per_group_type[$group_type_id],
      ]);

      // If there are no inheritance entities set up, we can bail here.
      $inheritances = $inheritance_storage->loadByProperties(['source' => $role_ids]);
      if (empty($inheritances)) {
        continue;
      }

      // If there are inheritance entities set up for this tree, any change to
      // the tree structure might alter the permissions, so we need to add the
      // tree's cache tag to the dependencies.
      $calculated_permissions->addCacheTags($group_handler->getTreeCacheTags($group));

      // We will keep track of:
      // - All the permissions we need to grant for a given group ID.
      // - All the group IDs that belong to the same tree as the membership's
      //   group and are of the target role's group type.
      $group_permission_sets = $group_ids = [];

      /** @var \Drupal\subgroup\Entity\RoleInheritanceInterface $inheritance */
      foreach ($inheritances as $inheritance) {
        // Inheritance entities cannot be updated, so no need to add them as
        // dependencies because adding or removing them already triggers the
        // custom list cache tag added above.
        $target_group_role = $inheritance->getTarget();
        $target_group_type_id = $target_group_role->getGroupTypeId();
        $target_group_type_depth = $group_type_handler->wrapLeaf($group_types[$target_group_type_id])->getDepth();

        // Figure out whether we need to go up or down the tree.
        $source_group_type_depth = $group_type_handler->wrapLeaf($group_types[$group_type_id])->getDepth();
        $search_upwards = $target_group_type_depth < $source_group_type_depth;

        if (!isset($group_ids[$target_group_type_id])) {
          $group_ids[$target_group_type_id] = $group_storage
            ->getQuery()
            ->condition('type', $target_group_type_id)
            ->condition(SUBGROUP_TREE_FIELD, $leaf->getTree())
            ->condition(SUBGROUP_LEFT_FIELD, $leaf->getLeft(), $search_upwards ? '<' : '>')
            ->condition(SUBGROUP_RIGHT_FIELD, $leaf->getRight(), $search_upwards ? '>' : '<')
            ->condition(SUBGROUP_DEPTH_FIELD, $target_group_type_depth)
            ->accessCheck(FALSE)
            ->execute();
        }

        if (!empty($group_ids[$target_group_type_id])) {
          // Add the permissions to the list of permission sets for the targets.
          foreach ($group_ids[$target_group_type_id] as $affected_group_id) {
            $group_permission_sets[$affected_group_id][] = $target_group_role->getPermissions();
          }

          // Because we used the role's permissions, it is now a dependency.
          $calculated_permissions->addCacheableDependency($target_group_role);
        }
      }

      foreach ($group_permission_sets as $group_id => $permission_sets) {
        $permissions = $permission_sets ? array_merge(...$permission_sets) : [];
        $item = new CalculatedGroupPermissionsItem(
          CalculatedGroupPermissionsItemInterface::SCOPE_GROUP,
          $group_id,
          $permissions
        );
        $calculated_permissions->addItem($item);
      }
    }

    return $calculated_permissions;
  }

}
