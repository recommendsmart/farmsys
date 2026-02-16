<?php

namespace Drupal\Tests\grequest\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests that all config provided by this module passes validation.
 *
 * @group grequest
 */
class GroupMembershipRequestConfigTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'views',
    'group',
    'options',
    'entity',
    'variationcache',
    'state_machine',
    'grequest',
  ];

  /**
   * Tests that the module's config installs properly.
   */
  public function testConfig() {
    $this->installConfig(['grequest']);
  }

}
