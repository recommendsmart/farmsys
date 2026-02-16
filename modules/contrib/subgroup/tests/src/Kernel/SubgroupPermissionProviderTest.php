<?php

namespace Drupal\Tests\subgroup\Kernel;

use Drupal\group\Plugin\GroupContentPermissionProvider;

/**
 * Tests that Subgroup defines the right group permissions.
 *
 * @coversDefaultClass \Drupal\subgroup\Plugin\SubgroupPermissionProvider
 * @group subgroup
 */
class SubgroupPermissionProviderTest extends SubgroupKernelTestBase {

  /**
   * Tests the defined permissions.
   */
  public function testPermissions() {
    $group_type_1 = $this->createGroupType();
    $group_type_2 = $this->createGroupType();

    /** @var \Drupal\subgroup\Entity\GroupTypeSubgroupHandler $group_type_handler */
    $group_type_handler = $this->entityTypeManager->getHandler('group_type', 'subgroup');
    $group_type_handler->initTree($group_type_1);
    $group_type_handler->addLeaf($group_type_1, $group_type_2);

    /** @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.group_content_enabler');
    $plugin_id = 'subgroup:' . $group_type_2->id();

    $subgroup_provider = $plugin_manager->getPermissionProvider($plugin_id);
    $default_provider = $plugin_manager->createHandlerInstance(GroupContentPermissionProvider::class, $plugin_id, $plugin_manager->getDefinition($plugin_id));

    $this->assertSame($default_provider->getPermission('view', 'relation', 'any'), $subgroup_provider->getPermission('view', 'relation', 'any'));
    $this->assertSame($default_provider->getPermission('view', 'relation', 'own'), $subgroup_provider->getPermission('view', 'relation', 'own'));
    $this->assertSame($default_provider->getPermission('update', 'relation', 'any'), $subgroup_provider->getPermission('update', 'relation', 'any'));
    $this->assertSame($default_provider->getPermission('update', 'relation', 'own'), $subgroup_provider->getPermission('update', 'relation', 'own'));
    $this->assertFalse($subgroup_provider->getPermission('delete', 'relation', 'any'), 'Subgroups cannot be orphaned.');
    $this->assertFalse($subgroup_provider->getPermission('delete', 'relation', 'own'), 'Subgroups cannot be orphaned.');
    $this->assertFalse($subgroup_provider->getPermission('create', 'relation'), 'Existing groups cannot be added as a subgroup.');

    $this->assertSame($default_provider->getPermission('view', 'entity', 'any'), $subgroup_provider->getPermission('view', 'entity', 'any'));
    $this->assertSame($default_provider->getPermission('view', 'entity', 'own'), $subgroup_provider->getPermission('view', 'entity', 'own'));
    $this->assertSame($default_provider->getPermission('update', 'entity', 'any'), $subgroup_provider->getPermission('update', 'entity', 'any'));
    $this->assertSame($default_provider->getPermission('update', 'entity', 'own'), $subgroup_provider->getPermission('update', 'entity', 'own'));
    $this->assertSame($default_provider->getPermission('delete', 'entity', 'any'), $subgroup_provider->getPermission('delete', 'entity', 'any'));
    $this->assertSame($default_provider->getPermission('delete', 'entity', 'own'), $subgroup_provider->getPermission('delete', 'entity', 'own'));
    $this->assertEquals("create $plugin_id entity", $subgroup_provider->getPermission('create', 'entity'), 'Groups can be created as subgroups.');
  }

}
