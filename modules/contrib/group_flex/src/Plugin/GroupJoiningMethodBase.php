<?php

namespace Drupal\group_flex\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Base class for Group joining method plugins.
 */
abstract class GroupJoiningMethodBase extends PluginBase implements GroupJoiningMethodInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public $entityTypeManager;

  /**
   * The group content enabler plugin.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  public $groupContentEnabler;

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
  public function __construct(array $configuration, string $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = \Drupal::service('entity_type.manager');
    $this->groupContentEnabler = \Drupal::service('plugin.manager.group_content_enabler');
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
  public function getVisibilityOptions(): array {
    return $this->pluginDefinition['visibilityOptions'];
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
  public function getGroupPermissions(GroupInterface $group): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDisallowedGroupPermissions(GroupInterface $group): array {
    return [];
  }

}
