<?php

namespace Drupal\commerce_funds\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks access for withdrawal method routes.
 *
 * @see \Drupal\Core\Access\CustomAccessCheck
 */
class WithdrawalMethodAccessCheck {

  /**
   * Checks access.
   *
   * Confirms that the user either has the 'administer withdrawal requests'
   * permission, or the 'withdraw funds' permission while
   * visiting the user withdrawal method pages.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function checkAccess(RouteMatchInterface $route_match, AccountInterface $account) {
    $result = AccessResult::allowedIfHasPermissions($account, [
      'administer withdrawal requests',
    ]);

    $current_user = $route_match->getParameter('user');
    if ($result->isNeutral() && $current_user->id() == $account->id()) {
      $result = AccessResult::allowedIfHasPermissions($account, [
        'withdraw funds',
      ])->cachePerUser();
    }

    return $result;
  }

}
