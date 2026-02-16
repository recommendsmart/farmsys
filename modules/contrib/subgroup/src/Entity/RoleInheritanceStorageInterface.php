<?php

namespace Drupal\subgroup\Entity;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Defines an interface for role inheritance entity storage classes.
 */
interface RoleInheritanceStorageInterface extends ConfigEntityStorageInterface {

  /**
   * Deletes all role inheritance entities for a given group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to delete the entities for.
   * @param string $tree
   *   The ID of the tree the group type belonged to. This must be provided
   *   separately as the provided group type may no longer be a leaf and can
   *   therefore not contain this information.
   */
  public function deleteForGroupType(GroupTypeInterface $group_type, $tree);

}
