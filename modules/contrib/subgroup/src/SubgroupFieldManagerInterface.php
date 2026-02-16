<?php

namespace Drupal\subgroup;

/**
 * Manages the group bundle fields needed for Subgroup to function.
 */
interface SubgroupFieldManagerInterface {

  /**
   * Installs the bundle fields on a group type.
   *
   * @param string $group_type_id
   *   The ID of group type to install the fields on.
   */
  public function installFields($group_type_id);

  /**
   * Deletes the bundle fields from a group type.
   *
   * @param string $group_type_id
   *   The ID of group type to delete the fields from.
   */
  public function deleteFields($group_type_id);

}
