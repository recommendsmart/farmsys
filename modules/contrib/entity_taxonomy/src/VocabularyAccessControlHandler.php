<?php

namespace Drupal\entity_taxonomy;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the entity_taxonomy vocabulary entity type.
 *
 * @see \Drupal\entity_taxonomy\Entity\Vocabulary
 */
class VocabularyAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'access entity_taxonomy overview':
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, ['access entity_taxonomy overview', 'administer entity_taxonomy'], 'OR');

      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }

}
