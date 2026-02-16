<?php

namespace Drupal\widget_type;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the widget type entity type.
 */
class WidgetTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    return $operation === 'view'
      ? AccessResult::allowedIfHasPermission($account, 'access content')
      : parent::checkAccess($entity, $operation, $account);
  }

}
