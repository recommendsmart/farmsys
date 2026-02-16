<?php

namespace Drupal\group_flex\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Base class for Group visibility plugins.
 */
abstract class GroupVisibilityBase extends PluginBase implements GroupVisibilityInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public $entityTypeManager;

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = \Drupal::service('entity_type.manager');
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return $this->pluginDefinition['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupPermissions(GroupInterface $group): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDisallowedGroupPermissions(GroupInterface $group): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function saveMappedPerm(array $mappedPerm, GroupTypeInterface $groupType) {
    foreach ($mappedPerm as $roleName => $permissions) {
      $groupRoleStorage = $this->entityTypeManager->getStorage('group_role');
      /** @var \Drupal\group\Entity\GroupRoleInterface $groupRole */
      $groupRole = $groupRoleStorage->load($roleName);

      if ($groupRole && !empty($permissions)) {
        foreach ($permissions as $perm => $value) {
          if ($value === TRUE) {
            $groupRole->grantPermission($perm);
            continue;
          }
          $groupRole->revokePermission($perm);
        }
        $groupRole->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupLabel(GroupTypeInterface $groupType): string {
    return $this->getValueDescription($groupType);
  }

  /**
   * {@inheritdoc}
   */
  public function getValueDescription(GroupTypeInterface $groupType): string {
    return '';
  }

}
