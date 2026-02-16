<?php

namespace Drupal\group_flex;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group_flex\Plugin\GroupJoiningMethodManager;
use Drupal\group_flex\Plugin\GroupVisibilityManager;

/**
 * Saves a group flex settings form in the group type interface.
 */
class GroupFlexGroupTypeSaver {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group visibility manager.
   *
   * @var \Drupal\group_flex\Plugin\GroupVisibilityManager
   */
  private $visibilityManager;

  /**
   * The group joining method manager.
   *
   * @var \Drupal\group_flex\Plugin\GroupJoiningMethodManager
   */
  private $joiningMethodManager;

  /**
   * Constructs a new GroupFlexGroupTypeSaver.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\group_flex\Plugin\GroupVisibilityManager $visibilityManager
   *   The group visibility manager.
   * @param \Drupal\group_flex\Plugin\GroupJoiningMethodManager $joiningMethodManager
   *   The group joining method manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, GroupVisibilityManager $visibilityManager, GroupJoiningMethodManager $joiningMethodManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->visibilityManager = $visibilityManager;
    $this->joiningMethodManager = $joiningMethodManager;
  }

  /**
   * Save the group flex settings.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The Group Type.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function save(GroupTypeInterface $groupType) {
    $this->saveGroupVisibility($groupType);
    $this->saveJoiningMethods($groupType);
  }

  /**
   * Save the group visibility settings.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The group type.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function saveGroupVisibility(GroupTypeInterface $groupType) {
    $visibility = $groupType->getThirdPartySetting('group_flex', 'group_type_visibility');
    /** @var \Drupal\group_flex\Plugin\GroupVisibilityBase $pluginInstance */
    $groupVisibilities = $this->getAllGroupVisibility();
    foreach ($groupVisibilities as $id => $pluginInstance) {
      if ($visibility !== $id) {
        $pluginInstance->disableGroupType($groupType);
      }
    }
    if (isset($groupVisibilities[$visibility])) {
      $groupVisibilities[$visibility]->enableGroupType($groupType);
    }
  }

  /**
   * Saves the joining methods for the given group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The group type.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function saveJoiningMethods(GroupTypeInterface $groupType) {
    $setJoiningMethods = $groupType->getThirdPartySetting('group_flex', 'group_type_joining_method', []);
    /** @var \Drupal\group_flex\Plugin\GroupJoiningMethodBase $pluginInstance */
    $joiningMethods = $this->getAllJoiningMethods();
    foreach ($joiningMethods as $id => $pluginInstance) {
      $status = $setJoiningMethods[$id] ?? 0;
      if ($status !== $id) {
        $pluginInstance->disableGroupType($groupType);
        continue;
      }
      $pluginInstance->enableGroupType($groupType);
    }
  }

  /**
   * Get all joining methods.
   *
   * @return array
   *   An array of joining methods containing the PluginInstances.
   */
  public function getAllJoiningMethods(): array {
    return $this->joiningMethodManager->getAllAsArray();
  }

  /**
   * Get all group visibility.
   *
   * @return array
   *   An array of group visibilities containing the PluginInstances.
   */
  public function getAllGroupVisibility(): array {
    return $this->visibilityManager->getAllAsArray();
  }

}
