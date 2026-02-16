<?php

namespace Drupal\Tests\entity_inherit\Unit\EntityInheritPlugin;

use Drupal\entity_inherit\EntityInheritPlugin\EntityInheritPluginManager;
use PHPUnit\Framework\TestCase;

/**
 * Test EntityInheritPluginManager.
 *
 * @group entity_inherit
 */
class EntityInheritPluginManagerTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(EntityInheritPluginManager::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertTrue(is_object($object));
  }

}
