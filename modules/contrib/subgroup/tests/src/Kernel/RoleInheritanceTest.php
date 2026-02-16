<?php

namespace Drupal\Tests\subgroup\Kernel;

use Drupal\Core\Cache\Cache;

/**
 * Tests the general behavior of role inheritance entities.
 *
 * @coversDefaultClass \Drupal\subgroup\Entity\RoleInheritance
 * @group subgroup
 */
class RoleInheritanceTest extends SubgroupKernelTestBase {

  /**
   * The role inheritance entity to run tests on.
   *
   * @var \Drupal\subgroup\Entity\RoleInheritanceInterface
   */
  protected $roleInheritance;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set up two group types to form a tree.
    $foo = $this->createGroupType(['id' => 'foo']);
    $bar = $this->createGroupType(['id' => 'bar']);

    /** @var \Drupal\subgroup\Entity\GroupTypeSubgroupHandler $group_type_handler */
    $group_type_handler = $this->entityTypeManager->getHandler('group_type', 'subgroup');
    $group_type_handler->initTree($foo);
    $group_type_handler->addLeaf($foo, $bar);

    $this->roleInheritance = $this->entityTypeManager->getStorage('subgroup_role_inheritance')->create([
      'id' => $this->randomMachineName(),
      'source' => 'foo-member',
      'target' => 'bar-member',
      // Prove that setting this does nothing.
      'tree' => $this->randomString(),
    ]);
    $this->roleInheritance->save();
  }

  /**
   * Tests the source role getter.
   *
   * @covers ::getSource
   */
  public function testGetSource() {
    $role = $this->entityTypeManager->getStorage('group_role')->load('foo-member');
    $this->assertEquals($role, $this->roleInheritance->getSource());
  }

  /**
   * Tests the source role ID getter.
   *
   * @covers ::getSourceId
   */
  public function testGetSourceId() {
    $this->assertEquals('foo-member', $this->roleInheritance->getSourceId());
  }

  /**
   * Tests the target role getter.
   *
   * @covers ::getTarget
   */
  public function testGetTarget() {
    $role = $this->entityTypeManager->getStorage('group_role')->load('bar-member');
    $this->assertEquals($role, $this->roleInheritance->getTarget());
  }

  /**
   * Tests the source role ID getter.
   *
   * @covers ::getTargetId
   */
  public function testGetTargetId() {
    $this->assertEquals('bar-member', $this->roleInheritance->getTargetId());
  }

  /**
   * Tests the tree ID getter.
   *
   * @covers ::getTree
   */
  public function testGetTree() {
    $this->assertEquals('foo', $this->roleInheritance->getTree(), 'Tree property was normalized to the tree ID of the tree the roles belong to.');
  }

  /**
   * Tests that the custom list cache tag is cleared.
   */
  public function testCustomListCacheTag() {
    $cache = $this->container->get('cache.static');

    $cache->set('will-not-be-cleared', 'some entry', Cache::PERMANENT, ['subgroup_role_inheritance_list:tree:unused']);
    $cache->set('will-be-cleared', 'some entry', Cache::PERMANENT, ['subgroup_role_inheritance_list:tree:foo']);
    $this->assertEquals('some entry', $cache->get('will-not-be-cleared')->data);
    $this->assertEquals('some entry', $cache->get('will-be-cleared')->data);

    $storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
    $storage->save($storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'bar-member',
      'target' => 'foo-member',
    ]));
    $this->assertEquals('some entry', $cache->get('will-not-be-cleared')->data);
    $this->assertFalse($cache->get('will-be-cleared'));

    $cache->set('will-not-be-cleared', 'some entry', Cache::PERMANENT, ['subgroup_role_inheritance_list:tree:unused']);
    $cache->set('will-be-cleared', 'some entry', Cache::PERMANENT, ['subgroup_role_inheritance_list:tree:foo']);
    $this->assertEquals('some entry', $cache->get('will-not-be-cleared')->data);
    $this->assertEquals('some entry', $cache->get('will-be-cleared')->data);

    $storage->delete([$this->roleInheritance]);
    $this->assertEquals('some entry', $cache->get('will-not-be-cleared')->data);
    $this->assertFalse($cache->get('will-be-cleared'));
  }

}
