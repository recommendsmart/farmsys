<?php

namespace Drupal\group_flex;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group_flex\Plugin\GroupJoiningMethodManager;
use Drupal\group_flex\Plugin\GroupVisibilityInterface;

/**
 * Get the group flex settings from a group type.
 */
class GroupFlexGroupType {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group joining method manager.
   *
   * @var \Drupal\group_flex\Plugin\GroupJoiningMethodManager
   */
  private $joiningMethodManager;

  /**
   * Constructs a new GroupFlexGroupType.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\group_flex\Plugin\GroupJoiningMethodManager $joiningMethodManager
   *   The group joining method manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, GroupJoiningMethodManager $joiningMethodManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->joiningMethodManager = $joiningMethodManager;
  }

  /**
   * Get whether flex is enabled for the group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The Group Type.
   *
   * @return bool
   *   Returns TRUE if group_flex_enabler is TRUE.
   */
  public function hasFlexEnabled(GroupTypeInterface $groupType): bool {
    return $groupType->getThirdPartySetting('group_flex', 'group_flex_enabler', FALSE);
  }

  /**
   * Get the group type visibility.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The Group Type.
   *
   * @return string|null
   *   Returns the visibility of the group.
   */
  public function getGroupTypeVisibility(GroupTypeInterface $groupType): ?string {
    return $groupType->getThirdPartySetting('group_flex', 'group_type_visibility');
  }

  /**
   * Returns TRUE if the group type visibility can be changed on group level.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The Group Type.
   *
   * @return bool
   *   Returns TRUE if the group type visibility is flexible.
   */
  public function hasFlexibleGroupTypeVisibility(GroupTypeInterface $groupType): bool {
    return $this->getGroupTypeVisibility($groupType) === GroupVisibilityInterface::GROUP_FLEX_TYPE_VIS_FLEX;
  }

  /**
   * Get the value from the group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The Group Type.
   *
   * @return array
   *   Returns an array of group type joining methods.
   */
  public function getJoiningMethods(GroupTypeInterface $groupType): array {
    return $groupType->getThirdPartySetting('group_flex', 'group_type_joining_method', []);
  }

  /**
   * Whether the creator can override the joining method on the group level.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The Group Type.
   *
   * @return bool
   *   Returns TRUE if creator can override joining method.
   */
  public function canOverrideJoiningMethod(GroupTypeInterface $groupType): bool {
    return $groupType->getThirdPartySetting('group_flex', 'group_type_joining_method_override', FALSE);
  }

  /**
   * Get the enabled joining method plugins for given group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The Group Type.
   *
   * @return array
   *   The array of enabled joining method plugins.
   */
  public function getEnabledJoiningMethodPlugins(GroupTypeInterface $groupType): array {
    $setJoiningMethods = $this->getJoiningMethods($groupType);
    $joiningMethodPlugins = $this->joiningMethodManager->getAllAsArray();

    foreach ($joiningMethodPlugins as $id => $unUsedPluginInstance) {
      if (!in_array($id, $setJoiningMethods, FALSE)) {
        // Unset the value when it is not in the enabled joining methods.
        unset($joiningMethodPlugins[$id]);
      }
    }
    return $joiningMethodPlugins;
  }

}
