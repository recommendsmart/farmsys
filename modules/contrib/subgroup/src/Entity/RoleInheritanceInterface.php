<?php

namespace Drupal\subgroup\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a role inheritance entity.
 */
interface RoleInheritanceInterface extends ConfigEntityInterface {

  /**
   * Returns the source group role.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface
   *   The source group role.
   */
  public function getSource();

  /**
   * Returns the ID of the source group role.
   *
   * @return string
   *   The ID of the source group role.
   */
  public function getSourceId();

  /**
   * Returns the target group role.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface
   *   The target group role.
   */
  public function getTarget();

  /**
   * Returns the ID of the target group role.
   *
   * @return string
   *   The ID of the target group role.
   */
  public function getTargetId();

  /**
   * Returns the ID of the tree the inheritance was set up for.
   *
   * @return string
   *   The tree ID.
   */
  public function getTree();

}
