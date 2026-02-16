<?php

namespace Drupal\group_flex\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Defines an interface for Group visibility plugins.
 */
interface GroupVisibilityInterface extends PluginInspectionInterface {

  /**
   * The string to use for group flex type 'public' visibility.
   */
  const GROUP_FLEX_TYPE_VIS_PUBLIC = 'public';

  /**
   * The string to use for group flex type 'private' visibility.
   */
  const GROUP_FLEX_TYPE_VIS_PRIVATE = 'private';

  /**
   * The string to use for group flex type 'flexible' visibility.
   */
  const GROUP_FLEX_TYPE_VIS_FLEX = 'flex';

  /**
   * The label of the group visibility.
   *
   * @return string
   *   The label of the group visibility.
   */
  public function getLabel(): string;

  /**
   * The weight of the group visibility.
   *
   * @return int
   *   The weight of the group visibility.
   */
  public function getWeight(): int;

  /**
   * Get all the group permissions for the visibility option.
   *
   * This can be used to save the group specific permissions
   * when this visibility option is enabled.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group.
   *
   * @return array
   *   An array of permissions keyed by the group role ids.
   */
  public function getGroupPermissions(GroupInterface $group): array;

  /**
   * Get all the disallowed group permissions for the visibility option.
   *
   * This can be used to remove the group specific permissions
   * when this visibility option is enabled.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group.
   *
   * @return array
   *   An array of permissions keyed by the group role ids.
   */
  public function getDisallowedGroupPermissions(GroupInterface $group): array;

  /**
   * Enable the visibility on the group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The group type.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function enableGroupType(GroupTypeInterface $groupType);

  /**
   * Disable the visibility on the group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The group type.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function disableGroupType(GroupTypeInterface $groupType);

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
   * The group label for this visibility.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The group type.
   *
   * @return string
   *   The group label for this visibility.
   */
  public function getGroupLabel(GroupTypeInterface $groupType): string;

  /**
   * The description for the group visibility.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The group type.
   *
   * @return string
   *   The description for the group visibility.
   */
  public function getValueDescription(GroupTypeInterface $groupType): string;

}
