<?php

namespace Drupal\Tests\subgroup\Unit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\subgroup\Entity\SubgroupHandlerBase;
use Drupal\subgroup\InvalidLeafException;
use Drupal\subgroup\InvalidParentException;
use Drupal\subgroup\InvalidRootException;
use Drupal\subgroup\LeafInterface;
use Drupal\subgroup\MalformedLeafException;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the functionality of the base subgroup handler.
 *
 * @coversDefaultClass \Drupal\subgroup\Entity\SubgroupHandlerBase
 * @group subgroup
 */
class SubgroupHandlerBaseTest extends UnitTestCase {

  /**
   * The base subgroup handler.
   *
   * @var \Drupal\subgroup\Entity\SubgroupHandlerBase
   */
  protected $subgroupHandler;

  /**
   * The entity to run tests on.
   *
   * @var \Drupal\Core\Entity\EntityInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entity;

  /**
   * The entity type to run tests on.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entity = $this->prophesize(EntityInterface::class);
    $this->entity->isNew()->willReturn(FALSE);
    $this->entity->getEntityTypeId()->willReturn('foo');

    // We hi-jack the uuid to pass on the leaf data.
    $this->entity->uuid()->willReturn('valid:0:1:2:9000');

    $this->entityType = $this->prophesize(EntityTypeInterface::class);
    $this->entityType->id()->willReturn('foo');
    $this->entityType->get('subgroup_wrapper')->willReturn(TestLeaf::class);

    $storage = $this->prophesize(EntityStorageInterface::class);

    $this->subgroupHandler = new TestSubgroubHandler($this->entityType->reveal(), $storage->reveal());
    $this->subgroupHandler->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * Tests the exception thrown when providing an invalid entity type.
   *
   * @covers ::verify
   */
  public function testIncorrectEntityTypeException() {
    $this->entity->getEntityTypeId()->willReturn('bar');
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Entity does not belong to the entity type the handler represents.');
    $this->subgroupHandler->isLeaf($this->prophesize(EntityInterface::class)->reveal());
  }

  /**
   * Tests the exception thrown when the wrapper class was not defined.
   *
   * @covers ::wrapLeaf
   */
  public function testWrapperClassUndefined() {
    $this->entityType->get('subgroup_wrapper')->willReturn(NULL);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('The "foo" entity type did not define a "subgroup_wrapper" class.');
    $this->subgroupHandler->wrapLeaf($this->entity->reveal());
  }

  /**
   * Tests the exception thrown when the wrapper class does not exist.
   *
   * @covers ::wrapLeaf
   */
  public function testWrapperClassMissing() {
    $this->entityType->get('subgroup_wrapper')->willReturn('Drupal\Tests\subgroup\Unit\Foo');
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('The "subgroup_wrapper" class defined by the "foo" entity type does not exist.');
    $this->subgroupHandler->wrapLeaf($this->entity->reveal());
  }

  /**
   * Tests the wrapLeaf() method.
   *
   * @covers ::wrapLeaf
   */
  public function testWrapLeaf() {
    $leaf = $this->subgroupHandler->wrapLeaf($this->entity->reveal());
    $this->assertInstanceOf(TestLeaf::class, $leaf);
  }

  /**
   * Tests the isLeaf() method.
   *
   * @covers ::isLeaf
   */
  public function testIsLeaf() {
    $this->assertTrue($this->subgroupHandler->isLeaf($this->entity->reveal()));
    $this->entity->uuid()->willReturn('invalid:0:1:2:9000');
    $this->assertFalse($this->subgroupHandler->isLeaf($this->entity->reveal()));
  }

  /**
   * Tests the isRoot() method.
   *
   * @covers ::isRoot
   */
  public function testIsRoot() {
    $this->assertTrue($this->subgroupHandler->isRoot($this->entity->reveal()));
    $this->entity->uuid()->willReturn('valid:1:2:3:9000');
    $this->assertFalse($this->subgroupHandler->isRoot($this->entity->reveal()));
  }

  /**
   * Tests the areVerticallyRelated() method.
   *
   * @covers ::areVerticallyRelated
   */
  public function testAreVerticallyRelated() {
    $father = $this->prophesize(EntityInterface::class);
    $father->getEntityTypeId()->willReturn('foo');
    $father->isNew()->willReturn(FALSE);
    $father->uuid()->willReturn('valid:0:1:8:9000');
    $father = $father->reveal();

    $son = $this->prophesize(EntityInterface::class);
    $son->getEntityTypeId()->willReturn('foo');
    $son->isNew()->willReturn(FALSE);
    $son->uuid()->willReturn('valid:1:2:3:9000');
    $son = $son->reveal();

    $daughter = $this->prophesize(EntityInterface::class);
    $daughter->getEntityTypeId()->willReturn('foo');
    $daughter->isNew()->willReturn(FALSE);
    $daughter->uuid()->willReturn('valid:1:4:7:9000');
    $daughter = $daughter->reveal();

    $grandson = $this->prophesize(EntityInterface::class);
    $grandson->getEntityTypeId()->willReturn('foo');
    $grandson->isNew()->willReturn(FALSE);
    $grandson->uuid()->willReturn('valid:2:5:6:9000');
    $grandson = $grandson->reveal();

    $stranger = $this->prophesize(EntityInterface::class);
    $stranger->getEntityTypeId()->willReturn('foo');
    $stranger->isNew()->willReturn(FALSE);
    $stranger->uuid()->willReturn('valid:0:1:2:666');
    $stranger = $stranger->reveal();

    // Direct lineage, order of arguments is irrelevant.
    $this->assertTrue($this->subgroupHandler->areVerticallyRelated($father, $son));
    $this->assertTrue($this->subgroupHandler->areVerticallyRelated($son, $father));
    $this->assertTrue($this->subgroupHandler->areVerticallyRelated($father, $daughter));
    $this->assertTrue($this->subgroupHandler->areVerticallyRelated($daughter, $father));
    $this->assertTrue($this->subgroupHandler->areVerticallyRelated($daughter, $grandson));
    $this->assertTrue($this->subgroupHandler->areVerticallyRelated($grandson, $daughter));
    $this->assertTrue($this->subgroupHandler->areVerticallyRelated($father, $grandson));
    $this->assertTrue($this->subgroupHandler->areVerticallyRelated($grandson, $father));

    // No direct lineage, order of arguments is irrelevant.
    $this->assertFalse($this->subgroupHandler->areVerticallyRelated($son, $daughter));
    $this->assertFalse($this->subgroupHandler->areVerticallyRelated($daughter, $son));
    $this->assertFalse($this->subgroupHandler->areVerticallyRelated($son, $grandson));
    $this->assertFalse($this->subgroupHandler->areVerticallyRelated($grandson, $son));

    // No lineage, order of arguments is irrelevant.
    $this->assertFalse($this->subgroupHandler->areVerticallyRelated($father, $stranger));
    $this->assertFalse($this->subgroupHandler->areVerticallyRelated($stranger, $father));
    $this->assertFalse($this->subgroupHandler->areVerticallyRelated($son, $stranger));
    $this->assertFalse($this->subgroupHandler->areVerticallyRelated($stranger, $son));
    $this->assertFalse($this->subgroupHandler->areVerticallyRelated($daughter, $stranger));
    $this->assertFalse($this->subgroupHandler->areVerticallyRelated($stranger, $daughter));
    $this->assertFalse($this->subgroupHandler->areVerticallyRelated($grandson, $stranger));
    $this->assertFalse($this->subgroupHandler->areVerticallyRelated($stranger, $grandson));
  }

  /**
   * Tests that you cannot initialize a tree using a new entity.
   *
   * @covers ::initTree
   */
  public function testInitUnsavedEntityException() {
    $this->entity->isNew()->willReturn(TRUE);
    $this->expectException(InvalidRootException::class);
    $this->expectExceptionMessage('Cannot use an unsaved entity as a tree root.');
    $this->subgroupHandler->initTree($this->entity->reveal());
  }

  /**
   * Tests that you cannot initialize a tree using an existing leaf.
   *
   * @covers ::initTree
   */
  public function testInitExistingLeafException() {
    $this->expectException(InvalidRootException::class);
    $this->expectExceptionMessage('The entity to use as a tree root is already a leaf.');
    $this->subgroupHandler->initTree($this->entity->reveal());
  }

  /**
   * Tests that you cannot add a leaf to a parent that is not a leaf.
   *
   * @covers ::addLeaf
   */
  public function testAddParentNotALeafException() {
    $root = $this->prophesize(EntityInterface::class);
    $root->getEntityTypeId()->willReturn('foo');
    $root->isNew()->willReturn(FALSE);
    $root->uuid()->willReturn('invalid:0:1:2:9000');

    $this->expectException(InvalidParentException::class);
    $this->expectExceptionMessage('The entity to use as the parent is not a leaf.');
    $this->subgroupHandler->addLeaf($root->reveal(), $this->entity->reveal());
  }

  /**
   * Tests that you cannot add a new entity to a tree.
   *
   * @covers ::addLeaf
   */
  public function testAddUnsavedEntityException() {
    $root = $this->prophesize(EntityInterface::class);
    $root->getEntityTypeId()->willReturn('foo');
    $root->isNew()->willReturn(FALSE);
    $root->uuid()->willReturn('valid:0:1:2:9000');

    $this->entity->isNew()->willReturn(TRUE);

    $this->expectException(InvalidLeafException::class);
    $this->expectExceptionMessage('Cannot use an unsaved entity as a leaf.');
    $this->subgroupHandler->addLeaf($root->reveal(), $this->entity->reveal());
  }

  /**
   * Tests that you cannot add a leaf that is already a leaf.
   *
   * @covers ::addLeaf
   */
  public function testAddExistingLeafException() {
    $root = $this->prophesize(EntityInterface::class);
    $root->getEntityTypeId()->willReturn('foo');
    $root->isNew()->willReturn(FALSE);
    $root->uuid()->willReturn('valid:0:1:2:9000');

    $this->expectException(InvalidLeafException::class);
    $this->expectExceptionMessage('The entity to add as the leaf is already a leaf.');
    $this->subgroupHandler->addLeaf($root->reveal(), $this->entity->reveal());
  }

  /**
   * Tests that you cannot add a leaf to a parent that is not a leaf.
   *
   * @covers ::removeLeaf
   */
  public function testRemoveNotALeafException() {
    $this->entity->uuid()->willReturn('invalid:0:1:2:9000');
    $this->expectException(InvalidLeafException::class);
    $this->expectExceptionMessage('The entity to remove is not a leaf.');
    $this->subgroupHandler->removeLeaf($this->entity->reveal());
  }

  /**
   * Tests that you cannot get the parent of a root leaf.
   *
   * @covers ::getParent
   */
  public function testGetParentException() {
    $this->expectException(InvalidLeafException::class);
    $this->expectExceptionMessage('Trying to get the parent of a root leaf.');
    $this->subgroupHandler->getParent($this->entity->reveal());
  }

  /**
   * Tests that you cannot get the ancestors of a root leaf.
   *
   * @covers ::getAncestors
   */
  public function testGetAncestorsException() {
    $this->expectException(InvalidLeafException::class);
    $this->expectExceptionMessage('Trying to get the ancestors of a root leaf.');
    $this->subgroupHandler->getAncestors($this->entity->reveal());
  }

  /**
   * Tests the getTreeCacheTags method().
   *
   * @covers ::getTreeCacheTags
   */
  public function testGetTreeCacheTags() {
    $cache_tags = $this->subgroupHandler->getTreeCacheTags($this->entity->reveal());
    $this->assertSame(['subgroup:tree:foo:9000'], $cache_tags, 'Cache tags properly use entity type ID and tree ID.');
  }

  /**
   * Tests that you cannot get the tree cache tags for a non-leaf entity.
   *
   * @covers ::getTreeCacheTags
   */
  public function testGetTreeCacheTagsNotALeafException() {
    $this->entity->uuid()->willReturn('invalid:0:1:2:9000');
    $this->expectException(InvalidLeafException::class);
    $this->expectExceptionMessage('The entity to get the tree cache tags for is not a leaf.');
    $this->subgroupHandler->getTreeCacheTags($this->entity->reveal());
  }

}

class TestSubgroubHandler extends SubgroupHandlerBase {

  /**
   * {@inheritdoc}
   */
  protected function writeLeafData(EntityInterface $entity, $depth, $left, $right, $tree) {}

  /**
   * {@inheritdoc}
   */
  protected function clearLeafData(EntityInterface $entity, $save) {}

  /**
   * {@inheritdoc}
   */
  protected function getDepthPropertyName() {}

  /**
   * {@inheritdoc}
   */
  protected function getLeftPropertyName() {}

  /**
   * {@inheritdoc}
   */
  protected function getRightPropertyName() {}

  /**
   * {@inheritdoc}
   */
  protected function getTreePropertyName() {}

}

class TestLeaf implements LeafInterface {

  protected $depth;
  protected $left;
  protected $right;
  protected $tree;

  /**
   * Constructs a new TestLeaf.
   */
  public function __construct(EntityInterface $entity) {
    [$valid, $this->depth, $this->left, $this->right, $this->tree] = explode(':', $entity->uuid());
    if ($valid === 'invalid') {
      throw new MalformedLeafException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDepth() {
    return (int) $this->depth;
  }

  /**
   * {@inheritdoc}
   */
  public function getLeft() {
    return (int) $this->left;
  }

  /**
   * {@inheritdoc}
   */
  public function getRight() {
    return (int) $this->right;
  }

  /**
   * {@inheritdoc}
   */
  public function getTree() {
    return (int) $this->tree;
  }

}
