<?php

namespace Drupal\Tests\subgroup\Kernel;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\subgroup\Entity\RoleInheritanceInterface;
use Drupal\subgroup\InvalidInheritanceException;

/**
 * Tests the general behavior of role inheritance storage.
 *
 * @coversDefaultClass \Drupal\subgroup\Entity\RoleInheritanceStorage
 * @group subgroup
 */
class RoleInheritanceStorageTest extends SubgroupKernelTestBase {

  /**
   * The role inheritance storage to run tests on.
   *
   * @var \Drupal\subgroup\Entity\RoleInheritanceStorageInterface
   */
  protected $storage;

  /**
   * The root group type to run tests on.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupTypeRoot;

  /**
   * The leaf group type to run tests on.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupTypeLeaf;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set up two group types to form a tree.
    $this->groupTypeRoot = $this->createGroupType(['id' => 'foo']);
    $this->groupTypeLeaf = $this->createGroupType(['id' => 'bar']);

    /** @var \Drupal\subgroup\Entity\GroupTypeSubgroupHandler $group_type_handler */
    $group_type_handler = $this->entityTypeManager->getHandler('group_type', 'subgroup');
    $group_type_handler->initTree($this->groupTypeRoot);
    $group_type_handler->addLeaf($this->groupTypeRoot, $this->groupTypeLeaf);

    $this->storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
  }

  /**
   * Tests the exception thrown when trying to update an inheritance.
   *
   * @covers ::save
   */
  public function testUpdateException() {
    $role_inheritance = $this->storage->create([
      'id' => 'test',
      'source' => 'foo-member',
      'target' => 'bar-member',
    ]);
    $role_inheritance->save();

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('Role inheritance entities may not be updated after creation.');
    $role_inheritance->save();
  }

  /**
   * Tests the exception thrown when the source property is missing.
   *
   * @covers ::save
   */
  public function testSourceMissingException() {
    $role_inheritance = $this->storage->create([
      'id' => $this->randomMachineName(),
      'target' => 'bar-member',
    ]);
    $this->expectException(EntityMalformedException::class);
    $this->expectExceptionMessage('Source property is required for a RoleInheritance entity.');
    $role_inheritance->save();
  }

  /**
   * Tests the exception thrown when the target property is missing.
   *
   * @covers ::save
   */
  public function testTargetMissingException() {
    $role_inheritance = $this->storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'foo-member',
    ]);
    $this->expectException(EntityMalformedException::class);
    $this->expectExceptionMessage('Target property is required for a RoleInheritance entity.');
    $role_inheritance->save();
  }

  /**
   * Tests the exception thrown when the source role is not for members.
   *
   * @covers ::save
   */
  public function testSourceNotForMembersException() {
    $this->createGroupType(['id' => 'baz']);
    $role_inheritance = $this->storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'baz-outsider',
      'target' => 'bar-member',
    ]);
    $this->expectException(InvalidInheritanceException::class);
    $this->expectExceptionMessage('Source role must be assignable to members.');
    $role_inheritance->save();
  }

  /**
   * Tests the exception thrown when the target role is not for members.
   *
   * @covers ::save
   */
  public function testTargetNotForMembersException() {
    $this->createGroupType(['id' => 'baz']);
    $role_inheritance = $this->storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'foo-member',
      'target' => 'baz-outsider',
    ]);
    $this->expectException(InvalidInheritanceException::class);
    $this->expectExceptionMessage('Target role must be assignable to members.');
    $role_inheritance->save();
  }

  /**
   * Tests the exception thrown when the source role does not belong to a leaf.
   *
   * @covers ::save
   */
  public function testSourceNotALeafException() {
    $this->createGroupType(['id' => 'baz']);
    $role_inheritance = $this->storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'baz-member',
      'target' => 'bar-member',
    ]);
    $this->expectException(InvalidInheritanceException::class);
    $this->expectExceptionMessage('Source role must belong to a group type that is part of a tree.');
    $role_inheritance->save();
  }

  /**
   * Tests the exception thrown when the target role does not belong to a leaf.
   *
   * @covers ::save
   */
  public function testTargetNotALeafException() {
    $this->createGroupType(['id' => 'baz']);
    $role_inheritance = $this->storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'foo-member',
      'target' => 'baz-member',
    ]);
    $this->expectException(InvalidInheritanceException::class);
    $this->expectExceptionMessage('Target role must belong to a group type that is part of a tree.');
    $role_inheritance->save();
  }

  /**
   * Tests the exception thrown when the roles belong to different trees.
   *
   * @covers ::save
   */
  public function testDifferentTreeException() {
    $baz = $this->createGroupType(['id' => 'baz']);

    /** @var \Drupal\subgroup\Entity\GroupTypeSubgroupHandler $group_type_handler */
    $group_type_handler = $this->entityTypeManager->getHandler('group_type', 'subgroup');
    $group_type_handler->initTree($baz);

    $role_inheritance = $this->storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'foo-member',
      'target' => 'baz-member',
    ]);
    $this->expectException(InvalidInheritanceException::class);
    $this->expectExceptionMessage('Source role and target role must belong to group types from the same tree.');
    $role_inheritance->save();
  }

  /**
   * Tests the exception thrown when the inheritance exists already.
   *
   * @covers ::save
   */
  public function testDuplicateCombinationException() {
    $role_inheritance = $this->storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'foo-member',
      'target' => 'bar-member',
    ]);
    $role_inheritance->save();

    $role_inheritance = $this->storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'foo-member',
      'target' => 'bar-member',
    ]);
    $this->expectException(InvalidInheritanceException::class);
    $this->expectExceptionMessage('The provided combination of source and destination role exists already.');
    $role_inheritance->save();
  }

  /**
   * Tests the exception thrown when the roles belong to more distant relatives.
   *
   * @covers ::save
   */
  public function testNotVerticallyRelatedException() {
    $baz = $this->createGroupType(['id' => 'baz']);

    /** @var \Drupal\subgroup\Entity\GroupTypeSubgroupHandler $group_type_handler */
    $group_type_handler = $this->entityTypeManager->getHandler('group_type', 'subgroup');
    $group_type_handler->addLeaf($this->groupTypeRoot, $baz);

    $role_inheritance = $this->storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'bar-member',
      'target' => 'baz-member',
    ]);
    $this->expectException(InvalidInheritanceException::class);
    $this->expectExceptionMessage('Source role and target role must belong to group types that are vertically related (e.g.: parent-grandson, not siblings).');
    $role_inheritance->save();
  }

  /**
   * Tests the deletion of role inheritance entities for a group type.
   *
   * @covers ::deleteForGroupType
   */
  public function testDeleteForGroupType() {
    $this->storage->create([
      'id' => 'test',
      'source' => 'foo-member',
      'target' => 'bar-member',
    ])->save();

    $this->assertCount(1, $this->storage->loadMultiple());
    $this->assertInstanceOf(RoleInheritanceInterface::class, $this->storage->load('test'));

    /** @var \Drupal\subgroup\Entity\GroupTypeSubgroupHandler $group_type_handler */
    $group_type_handler = $this->entityTypeManager->getHandler('group_type', 'subgroup');
    $group_type_handler->removeLeaf($this->groupTypeLeaf);

    $this->assertCount(0, $this->storage->loadMultiple());
    $this->assertNull($this->storage->load('test'));
  }

}
