<?php

namespace Drupal\flow_ui;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an entity access control handler regarding flow config entities.
 */
class FlowUiAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\flow\Entity\FlowInterface $entity */
    return parent::checkAccess($entity, $operation, $account)
      ->orIf(AccessResult::allowedIfHasPermission($account, 'administer ' . $entity->getTargetEntityTypeId() . ' flow'));
  }

}
