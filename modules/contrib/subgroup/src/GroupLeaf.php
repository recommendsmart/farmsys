<?php

namespace Drupal\subgroup;

use Drupal\group\Entity\GroupInterface;

/**
 * Wrapper class for a Group entity representing a tree leaf.
 *
 * Should only be loaded through the 'subgroup' entity type handler.
 */
class GroupLeaf implements LeafInterface {

  /**
   * The group entity to wrap.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * Constructs a new GroupLeaf.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity to wrap the leaf around.
   */
  public function __construct(GroupInterface $group) {
    $field_names = [
      SUBGROUP_DEPTH_FIELD,
      SUBGROUP_LEFT_FIELD,
      SUBGROUP_RIGHT_FIELD,
      SUBGROUP_TREE_FIELD,
    ];

    foreach ($field_names as $field_name) {
      if (!$group->hasField($field_name)) {
        throw new MalformedLeafException(sprintf('Trying to create a group leaf but the "%s" field is missing', $field_name));
      }
      if ($group->get($field_name)->isEmpty()) {
        throw new MalformedLeafException(sprintf('Trying to create a group leaf but the "%s" value is missing', $field_name));
      }
    }

    $this->group = $group;
  }

  /**
   * {@inheritdoc}
   */
  public function getDepth() {
    return $this->getTypeSafeValue(SUBGROUP_DEPTH_FIELD);
  }

  /**
   * {@inheritdoc}
   */
  public function getLeft() {
    return $this->getTypeSafeValue(SUBGROUP_LEFT_FIELD);
  }

  /**
   * {@inheritdoc}
   */
  public function getRight() {
    return $this->getTypeSafeValue(SUBGROUP_RIGHT_FIELD);
  }

  /**
   * {@inheritdoc}
   */
  public function getTree() {
    return $this->getTypeSafeValue(SUBGROUP_TREE_FIELD);
  }

  /**
   * Gets the type-safe value from a subgroup field.
   *
   * @param string $field_name
   *   The name of the field to get the value of.
   *
   * @return int
   *   The type-safe value of the field. In this implementation, an integer.
   */
  protected function getTypeSafeValue($field_name) {
    return $this->group->get($field_name)->first()->get('value')->getCastedValue();
  }

}
