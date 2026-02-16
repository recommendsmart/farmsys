<?php

namespace Drupal\Tests\entity_inherit\Unit;

use PHPUnit\Framework\TestCase;
use Drupal\entity_inherit\EntityInherit;
use Drupal\entity_inherit\EntityInheritEvent\EntityInheritEventBase;
use Drupal\entity_inherit\EntityInheritEvent\EntityInheritEventInterface;

/**
 * Base class for testing.
 */
class EntityInheritTestBase extends TestCase {

  /**
   * Get dummy (mock) app.
   *
   * @return \Drupal\entity_inherit\EntityInherit
   *   A dummy (mock) app.
   */
  public function mockApp() : EntityInherit {
    // @codingStandardsIgnoreStart
    return new class() extends EntityInherit {
      public function __construct() {}
    };
    // @codingStandardsIgnoreEnd
  }

  /**
   * Get dummy (mock) event.
   *
   * @return \Drupal\entity_inherit\EntityInheritEvent\EntityInheritEventInterface
   *   A dummy (mock) event.
   */
  public function mockEvent() : EntityInheritEventInterface {
    return new EntityInheritEventBase([], 0);
  }

}
