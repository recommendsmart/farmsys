<?php

namespace Drupal\Tests\entity_inherit\Unit\EntityInheritPlugin;

use Drupal\entity_inherit\EntityInheritPlugin\EntityInheritPluginBase;
use PHPUnit\Framework\TestCase;

/**
 * Test EntityInheritPluginBase.
 *
 * @group entity_inherit
 */
class EntityInheritPluginBaseTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(EntityInheritPluginBase::class)
      ->setMethods([])
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();

    $this->assertTrue(is_object($object));
  }

}
