<?php

namespace Drupal\group_flex_content\Plugin;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Plugin\GroupContentAccessControlHandler as GroupContentAccessControlHandlerBase;

/**
 * Provides access control for GroupContent entities and grouped entities.
 */
class GroupContentAccessControlHandler extends GroupContentAccessControlHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account, $return_as_object = FALSE) {
    $parentAccess = parent::entityAccess($entity, $operation, $account, $return_as_object);

    // We don't have an opinion for all operations.
    if ($operation !== 'view') {
      return $parentAccess;
    }

    /** @var \Drupal\group\Entity\Storage\GroupContentStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_content');
    $group_contents = $storage->loadByEntity($entity);

    // Filter out the content that does not use this plugin.
    foreach ($group_contents as $id => $group_content) {
      $plugin_id = $group_content->getContentPlugin()->getPluginId();
      if ($plugin_id !== $this->pluginId) {
        unset($group_contents[$id]);
      }
    }

    // If this plugin is not being used by the entity, we have nothing to say.
    if (empty($group_contents)) {
      return $parentAccess;
    }

    // Now we loop through the group content and see if we need to allow
    // access based on any of the group content visibility values.
    foreach ($group_contents as $id => $group_content) {
      if (!$group_content->hasField('content_visibility') || empty($group_content->get('content_visibility')->getValue())) {
        continue;
      }

      $content_visibility = $group_content->get('content_visibility')->getValue()[0]['value'];
      if ((($content_visibility === 'outsider' && $account->isAuthenticated()) ||
          ($content_visibility === 'anonymous'))
        && $group_content->getEntity()->isPublished()) {
        $result = AccessResult::allowed();
        $result->cachePerUser();
        $result->addCacheableDependency($entity);
        return $return_as_object ? $result : $result->isAllowed();
      }
    }

    // Fallback to parent access.
    return $parentAccess;
  }

}
