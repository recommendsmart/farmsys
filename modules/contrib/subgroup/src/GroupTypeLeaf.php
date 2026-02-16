<?php

namespace Drupal\subgroup;

use Drupal\group\Entity\GroupTypeInterface;

/**
 * Wrapper class for a GroupType entity representing a tree leaf.
 *
 * Should only be loaded through the 'subgroup' entity type handler.
 */
class GroupTypeLeaf implements LeafInterface {

  /**
   * The group entity to wrap.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupType;

  /**
   * Constructs a new GroupLeaf.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group entity to wrap the leaf around.
   */
  public function __construct(GroupTypeInterface $group_type) {
    $settings = $group_type->getThirdPartySettings('subgroup');
    $setting_names = [
      SUBGROUP_DEPTH_SETTING,
      SUBGROUP_LEFT_SETTING,
      SUBGROUP_RIGHT_SETTING,
      SUBGROUP_TREE_SETTING,
    ];

    foreach ($setting_names as $setting_name) {
      if (!isset($settings[$setting_name])) {
        throw new MalformedLeafException(sprintf('Trying to create a group type leaf but "%s" is missing', $setting_name));
      }
    }

    $this->groupType = $group_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getDepth() {
    return $this->groupType->getThirdPartySetting('subgroup', SUBGROUP_DEPTH_SETTING);
  }

  /**
   * {@inheritdoc}
   */
  public function getLeft() {
    return $this->groupType->getThirdPartySetting('subgroup', SUBGROUP_LEFT_SETTING);
  }

  /**
   * {@inheritdoc}
   */
  public function getRight() {
    return $this->groupType->getThirdPartySetting('subgroup', SUBGROUP_RIGHT_SETTING);
  }

  /**
   * {@inheritdoc}
   */
  public function getTree() {
    return $this->groupType->getThirdPartySetting('subgroup', SUBGROUP_TREE_SETTING);
  }

}
