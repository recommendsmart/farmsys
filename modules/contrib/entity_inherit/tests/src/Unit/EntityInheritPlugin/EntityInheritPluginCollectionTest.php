<?php

namespace Drupal\Tests\entity_inherit\Unit\EntityInheritPlugin;

use Drupal\entity_inherit\EntityInheritPlugin\EntityInheritPluginCollection;
use PHPUnit\Framework\TestCase;

/**
 * Test EntityInheritPluginCollection.
 *
 * @group entity_inherit
 */
class EntityInheritPluginCollectionTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(EntityInheritPluginCollection::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertTrue(is_object($object));
  }

}
