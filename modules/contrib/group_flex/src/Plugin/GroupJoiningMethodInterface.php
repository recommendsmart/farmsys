<?php

namespace Drupal\group_flex\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Defines an interface for Group joining method plugins.
 */
interface GroupJoiningMethodInterface extends PluginInspectionInterface {

  /**
   * The label of the group joining method.
   *
   * @return string
   *   The label of the group joining method.
   */
  public function getLabel(): string;

  /**
   * The weight of the group joining method.
   *
   * @return int
   *   The weight of the group joining method.
   */
  public function getWeight(): int;

  /**
   * The visibility options where plugin should be available.
   *
   * Defaults to false which reflects all.
   *
   * @return array|false
   *   The available visibility options for the group joining method.
   */
  public function getVisibilityOptions();

  /**
   * Save the mapped permissions.
   *
   * @param array $mappedPerm
   *   The Mapped permissions, keyed by group type role id.
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The Group Type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveMappedPerm(array $mappedPerm, GroupTypeInterface $groupType);

  /**
   * Enable the joining method on the group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The group type.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function enableGroupType(GroupTypeInterface $groupType);

  /**
   * Disable the joining method on the group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The group type.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function disableGroupType(GroupTypeInterface $groupType);

  /**
   * Get all the group permissions for the joining method.
   *
   * This can be used to save the group specific permissions
   * when this joining method is enabled.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group.
   *
   * @return array
   *   An array of permissions keyed by the group role ids.
   */
  public function getGroupPermissions(GroupInterface $group): array;

  /**
   * Get all the disallowed group permissions for the joining method.
   *
   * This can be used to remove the group specific permissions
   * when this joining method is enabled.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group.
   *
   * @return array
   *   An array of permissions keyed by the group role ids.
   */
  public function getDisallowedGroupPermissions(GroupInterface $group): array;

}
