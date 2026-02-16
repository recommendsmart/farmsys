<?php

namespace Drupal\commerceg\Hook;

use Drupal\group\Access\GroupPermissionCheckerInterface;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

use Symfony\Component\Routing\RouterInterface;

/**
 * Holds methods implementing hooks related to entity operations.
 *
 * Since actual functionality is provided by Commerce Group submodules or other
 * contrib modules, here we provide a helper service that will allow other
 * modules to implement hooks with minimal code.
 */
class EntityOperation {

  use StringTranslationTrait;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The group permission checker.
   *
   * @var \Drupal\group\Access\GroupPermissionCheckerInterface
   */
  protected $groupPermissionChecker;

  /**
   * The router.
   *
   * @var \Symfony\Component\Routing\RouterInterface
   */
  protected $router;

  /**
   * Constructs a new EntityOperation object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   *   The current user account proxy.
   * @param \Drupal\group\Access\GroupPermissionCheckerInterface $group_permission_checker
   *   The group permission checker.
   * @param \Symfony\Component\Routing\RouterInterface $router
   *   The router.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    AccountProxyInterface $account_proxy,
    GroupPermissionCheckerInterface $group_permission_checker,
    RouterInterface $router,
    TranslationInterface $string_translation
  ) {
    $this->account = $account_proxy->getAccount();
    $this->groupPermissionChecker = $group_permission_checker;
    $this->router = $router;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Implements hook_entity_operation().
   *
   * Adds an operation to groups that links to the page with the given route and
   * taking into account the given group permission i.e. the link will be
   * displayed only if the current user has the given permission in the group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   * @param string $group_permission
   *   The group permissions that is required to access the operation link.
   * @param string $route_name
   *   The name of the route that provides the linked page.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The translated label of the operation.
   * @param int $weight
   *   Optionally, the weight of the operation. By default, we want all links to
   *   group content list pages to be listed in alphabetical order after the
   *   Members page (`group_membership` group content list page) provided by the
   *   Group module, and which has a weight of 15. We therefore set the weight
   *   for all them to 20 by default.
   *
   * @return array
   *   An associative array of operation link data.
   *   See \Drupal\Core\Entity\EntityListBuilderInterface::getOperations().
   */
  public function groupOperation(
    EntityInterface $group,
    $group_permission,
    $route_name,
    TranslatableMarkup $label,
    $weight = 20
  ) {
    if ($group->getEntityTypeId() !== 'group') {
      return [];
    }

    $has_permission = $this->groupPermissionChecker->hasPermissionInGroup(
      $group_permission,
      $this->account,
      $group
    );
    if (!$has_permission) {
      return [];
    }

    $route = $this->router
      ->getRouteCollection()
      ->get($route_name);
    if (!$route) {
      return [];
    }

    return [
      $route_name => [
        'title' => $label,
        'weight' => $weight,
        'url' => Url::fromRoute(
          $route_name,
          ['group' => $group->id()]
        ),
      ],
    ];
  }

}
