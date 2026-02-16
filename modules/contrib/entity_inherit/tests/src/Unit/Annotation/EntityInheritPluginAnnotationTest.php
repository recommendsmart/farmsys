<?php

namespace Drupal\Tests\entity_inherit\Unit\Annotation;

use Drupal\entity_inherit\Annotation\EntityInheritPluginAnnotation;
use PHPUnit\Framework\TestCase;

/**
 * Test EntityInheritPluginAnnotation.
 *
 * @group entity_inherit
 */
class EntityInheritPluginAnnotationTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(EntityInheritPluginAnnotation::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertTrue(is_object($object));
  }

}
