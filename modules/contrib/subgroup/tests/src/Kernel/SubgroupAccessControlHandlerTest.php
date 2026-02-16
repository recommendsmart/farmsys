<?php

namespace Drupal\Tests\subgroup\Kernel;

use Drupal\group\Plugin\GroupContentAccessControlHandler;

/**
 * Tests that Subgroup defines the right access control.
 *
 * @coversDefaultClass \Drupal\subgroup\Plugin\SubgroupPermissionProvider
 * @group subgroup
 */
class SubgroupAccessControlHandlerTest extends SubgroupKernelTestBase {

  /**
   * Tests the defined permissions.
   */
  public function testPermissions() {
    $group_type_1 = $this->createGroupType(['creator_membership' => FALSE]);
    $group_type_2 = $this->createGroupType(['creator_membership' => FALSE]);

    /** @var \Drupal\subgroup\Entity\GroupTypeSubgroupHandler $group_type_handler */
    $group_type_handler = $this->entityTypeManager->getHandler('group_type', 'subgroup');
    $group_type_handler->initTree($group_type_1);
    $group_type_handler->addLeaf($group_type_1, $group_type_2);

    /** @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.group_content_enabler');
    $plugin_id = 'subgroup:' . $group_type_2->id();

    $subgroup_handler = $plugin_manager->getAccessControlHandler($plugin_id);
    $default_handler = $plugin_manager->createHandlerInstance(GroupContentAccessControlHandler::class, $plugin_id, $plugin_manager->getDefinition($plugin_id));

    $group_type_1->getOutsiderRole()->grantPermission("create $plugin_id entity")->save();
    $group = $this->createGroup(['type' => $group_type_1->id()]);

    $this->assertFalse($default_handler->entityCreateAccess($group, $this->getCurrentUser()), 'Normally you would not be able to create subgroups');
    $this->assertTrue($subgroup_handler->entityCreateAccess($group, $this->getCurrentUser()), 'The subgroup handler allows you to create subgroups');
  }

}
