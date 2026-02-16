<?php

namespace Drupal\Tests\entity_inherit\Unit;

use Drupal\entity_inherit\EntityInherit;

/**
 * Test EntityInherit.
 *
 * @group entity_inherit
 */
class EntityInheritTest extends EntityInheritTestBase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(EntityInherit::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertTrue(is_object($object));
  }

}
