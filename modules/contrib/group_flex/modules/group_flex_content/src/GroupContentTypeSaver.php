<?php

namespace Drupal\group_flex_content;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\group\Entity\GroupContentTypeInterface;

/**
 * Saves a group content type.
 */
class GroupContentTypeSaver {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new GroupContentTypeSaver.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Save the group content type settings.
   *
   * @param \Drupal\group\Entity\GroupContentTypeInterface $groupContentType
   *   The Group Content Type.
   * @param string $visibility
   *   The visibility.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function save(GroupContentTypeInterface $groupContentType, string $visibility) {
    if (!$visibility) {
      return;
    }

    $groupType = $groupContentType->getGroupType();
    $groupFlexEnabled = $groupType->getThirdPartySetting('group_flex', 'group_flex_enabler');
    if (!$groupFlexEnabled) {
      return;
    }

    $contentPlugin = $groupContentType->getContentPlugin();
    $permission = 'view group_node:' . $contentPlugin->getEntityBundle() . ' entity';
    $visibility_permissions = [
      'use visibility anonymous for group_node:' . $contentPlugin->getEntityBundle() . ' entity',
      'use visibility outsider for group_node:' . $contentPlugin->getEntityBundle() . ' entity',
      'use visibility member for group_node:' . $contentPlugin->getEntityBundle() . ' entity',
    ];
    switch ($visibility) {
      case 'anonymous':
        $groupType->getAnonymousRole()->grantPermission($permission)->save();
        $groupType->getOutsiderRole()->grantPermission($permission)->save();
        $groupType->getMemberRole()->grantPermission($permission)->save();
        break;

      case 'outsider':
        $groupType->getAnonymousRole()->revokePermission($permission)->save();
        $groupType->getOutsiderRole()->grantPermission($permission)->save();
        $groupType->getMemberRole()->grantPermission($permission)->save();
        break;

      case 'member':
        $groupType->getAnonymousRole()->revokePermission($permission)->save();
        $groupType->getOutsiderRole()->revokePermission($permission)->save();
        $groupType->getMemberRole()->grantPermission($permission)->save();
        break;

      case 'flexible':
        $groupType->getAnonymousRole()->revokePermission($permission)->save();
        $groupType->getOutsiderRole()->revokePermission($permission)->save();
        $groupType->getMemberRole()->grantPermission($permission)->save();
        $groupType->getMemberRole()->grantPermissions($visibility_permissions)->save();
        $bundle = $groupContentType->id();

        // Add the content_visibility field to the added group content type.
        // The field storage for this is defined in the config/install folder.
        FieldConfig::create([
          'field_storage' => FieldStorageConfig::loadByName('group_content', 'content_visibility'),
          'bundle' => $bundle,
          'label' => t('Content visibility'),
          'description' => t('Set the content visibility.'),
        ])->save();

        break;

    }

  }

}
