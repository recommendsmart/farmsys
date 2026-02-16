<?php

namespace Drupal\commerceg_cart\Hook\Context;

use Drupal\commerceg_context\Context\ManagerInterface as ContextManagerInterface;
use Drupal\commerceg_context\Exception\InvalidConfigurationException;
use Drupal\group\Entity\GroupInterface;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Holds methods implementing hook_query_alter or hook_query_alter().
 *
 * This service is defined only when the Context submodule is enabled; we don't
 * make any alterations to the cart data query otherwise.
 *
 * @I Do not assume the current user in cart query alterations
 *    type     : bug
 *    priority : normal
 *    labels   : 1.0@alpha, cart, context
 *    notes    : We currently assume that we are loading carts for the current
 *               user. This is not necessarily the case, the cart provider that
 *               issues the query may have been given another user instead. What
 *               context do we assume if the user is not the current one though?
 */
class QueryAlter {

  /**
   * The shopping context manager.
   *
   * @var \Drupal\commerceg_context\Context\ManagerInterface
   */
  protected $contextManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The context module settings configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a new QueryAlter object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user account proxy.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\commerceg_context\Context\ManagerInterface $context_manager
   *   The shopping context manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    AccountProxyInterface $account,
    ConfigFactoryInterface $config_factory,
    ContextManagerInterface $context_manager,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->account = $account->getAccount();
    $this->config = $config_factory->get('commerceg_context.settings');
    $this->contextManager = $context_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Implements hook_query_TAG_alter().
   *
   * We alter the query issued by the cart provider to load the current user's
   * carts. We want to load the carts that belong to the current user's current
   * context instead.
   *
   * @param \Drupal\Core\Database\Query\AlterableInterface $query
   *   A Query object describing the composite parts of a SQL query.
   */
  public function commerceCartLoadData(AlterableInterface $query) {
    if (!$this->config->get('status')) {
      return;
    }

    if (!$query instanceof SelectInterface) {
      return;
    }

    $context = $this->contextManager->get();
    if ($context) {
      $this->alterContextQuery($query, $context);
      return;
    }

    // If the user does not have a current context, assume personal context. In
    // that case, we exclude carts that belong to any group context.
    $this->alterNoContextQuery($query);
  }

  /**
   * Alters the cart query when there is a shopping context.
   *
   * @param \Drupal\Core\Database\Query\AlterableInterface $query
   *   A Query object describing the composite parts of a SQL query.
   * @param \Drupal\group\Entity\GroupInterface $context
   *   The user's shopping context.
   */
  protected function alterContextQuery(
    AlterableInterface $query,
    GroupInterface $context
  ) {
    // Remove the condition that limits carts to the ones owned by the user.
    $conditions = &$query->conditions();
    foreach ($conditions as $index => &$condition) {
      if (!is_array($condition)) {
        continue;
      }
      if ($condition['field'] === 'o.uid') {
        unset($conditions[$index]);
        break;
      }
    }

    // Alter the query to load only carts that belong to a group that the
    // current user is a member of as well.
    // @I Review if we need access control as well i.e. check group permissions
    //    type     : bug
    //    priority : high
    //    labels   : context, security, 1.0@alpha
    $data_table = $this->entityTypeManager
      ->getDefinition('group_content')
      ->getDataTable();
    $query->leftJoin(
      $data_table,
      'gcfd',
      'o.order_id = gcfd.entity_id'
    );
    $query->leftJoin(
      $data_table,
      'gcfdu',
      'gcfd.gid = gcfdu.gid'
    );

    [$order_plugin_ids, $user_plugin_ids] = $this->getPluginIds();
    $query->condition('gcfd.type', $order_plugin_ids, 'IN');
    $query->condition('gcfdu.type', $user_plugin_ids, 'IN');
    $query->condition('gcfd.gid', $context->id());
    $query->condition('gcfdu.entity_id', $this->account->id());
  }

  /**
   * Alters the cart query when there is no group shopping context.
   *
   * @param \Drupal\Core\Database\Query\AlterableInterface $query
   *   A Query object describing the composite parts of a SQL query.
   */
  protected function alterNoContextQuery(AlterableInterface $query) {
    // We load carts that belong to the user, but those could have been created
    // on behalf of a group and the user should not have access to them in the
    // personal context. We therefore exclude carts that belong to context
    // groups but we allow non-context group carts.
    // @I Review cart query in scenarios with multiple group types
    //    type     : bug
    //    priority : high
    //    labels   : 1.0@alpha
    //    notes    : Orders that do belong to a context group and also to
    //               another non-context group will have two group content
    //               records and they will be picked up by the non-context group
    //               content record. If the user doesn't have permissions they
    //               will still be filtered out, but if they do have permissions
    //               they still shouldn't display on a non-context cart page.
    // @I Query picks up group content for other entity types
    //    type     : bug
    //    priority : high
    //    labels   : 1.0@alpha
    //    notes    : The query allows for non-context group carts in case orders
    //               are made content to groups of other custom group
    //               types. This, however, may pick up irrelevant group content
    //               i.e. referencing entities of different type that happen to
    //               have the same ID with a cart.
    $data_table = $this->entityTypeManager
      ->getDefinition('group_content')
      ->getDataTable();
    $query->leftJoin(
      $data_table,
      'gcfd',
      'o.order_id = gcfd.entity_id'
    );

    [$order_plugin_ids] = $this->getPluginIds();
    $or_condition = $query->orConditionGroup()
      ->condition('gcfd.type', NULL, 'IS NULL')
      ->condition('gcfd.type', $order_plugin_ids, 'NOT IN');
    $query->condition($or_condition);
  }

  /**
   * Loads the plugin IDs for order and user memberships.
   *
   * We only load plugin IDs that define group content types for the group type
   * configured to act as the context. Also, we assume the default user
   * membership plugin and the order membership plugin provided by
   * `commerceg_order`. It is extremely rare to have other plugins for the same
   * entities (`user`, `commerce_order`), and if there are they would most
   * likely serve a different purpose, so it's not worth making things more
   * complicated.
   *
   * @return array
   *   An array containing the order membership plugin IDs as its first element,
   *   and the user membership plugin IDs as its second element.
   */
  protected function getPluginIds() {
    $group_type_id = $this->config->get('group_context.group_type');
    $group_content_types = $this->entityTypeManager
      ->getStorage('group_content_type')
      ->loadByProperties(
        ['group_type' => $group_type_id]
      );

    $order_plugin_ids = [];
    $user_plugin_ids = [];
    foreach ($group_content_types as $group_content_type) {
      $plugin_id = $group_content_type->getContentPluginId();
      if ($plugin_id === 'group_membership') {
        $user_plugin_ids[] = $group_content_type->id();
        continue;
      }

      if (strpos($plugin_id, 'commerceg_order:') === 0) {
        $order_plugin_ids[] = $group_content_type->id();
      }
    }

    $this->validatePluginIds(
      $order_plugin_ids,
      $user_plugin_ids,
      $group_type_id
    );

    return [$order_plugin_ids, $user_plugin_ids];
  }

  /**
   * Validates that plugins are installed for orders/users for the group type.
   *
   * @param string[] $order_plugin_ids
   *   The plugin IDs for orders.
   * @param string[] $user_plugin_ids
   *   The plugin IDs for users.
   * @param string $group_type_id
   *   The ID of the group type that acts as the context.
   *
   * @throws \Drupal\commerceg\Exception\InvalidConfigurationException
   *   When there are no plugins installed for making orders or users available
   *   as group content to the group type with the given ID.
   */
  protected function validatePluginIds(
    array $order_plugin_ids,
    array $user_plugin_ids,
    $group_type_id
  ) {
    if (!$order_plugin_ids) {
      throw new InvalidConfigurationException(
        sprintf(
          'No order types are configured to be available as group content for the `%s` group type.',
          $group_type_id
        )
      );
    }
    if (!$user_plugin_ids) {
      throw new InvalidConfigurationException(
        sprintf(
          'Users are not configured to be available as group content for the `%s` group type.',
          $group_type_id
        )
      );
    }
  }

}
