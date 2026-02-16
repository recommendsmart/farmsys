<?php

namespace Drupal\Tests\entity_inherit\Unit\EntityInheritEntity;

use Drupal\entity_inherit\EntityInheritEntity\EntityInheritEntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * Test EntityInheritEntityFactory.
 *
 * @group entity_inherit
 */
class EntityInheritEntityFactoryTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(EntityInheritEntityFactory::class)
      ->setMethods([])
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();

    $this->assertTrue(is_object($object));
  }

}
