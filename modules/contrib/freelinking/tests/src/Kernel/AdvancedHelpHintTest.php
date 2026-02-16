<?php

namespace Drupal\Tests\freelinking\Kernel;

use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests hook_help implementation.
 *
 * @group freelinking
 * @requires module advanced_help
 */
class AdvancedHelpHintTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'freelinking',
  ];

  /**
   * Asserts hook_help() is functional.
   *
   * @param array $modules
   *   An array of optional module dependencies.
   *
   * @dataProvider hookHelpProvider
   *
   * @throws \Exception
   */
  public function testHookHelp(array $modules = []) {
    $routeMatchProphet = $this->prophesize('\Drupal\Core\Routing\RouteMatchInterface');
    $routeMatch = $routeMatchProphet->reveal();

    if (!empty($modules)) {
      $module_handler = $this->container->get('module_handler');
      $discovery = new ExtensionDiscovery($this->root);
      $discovery->setProfileDirectories([]);
      $list = $discovery->scan('module');
      foreach ($modules as $name) {
        if (!isset($list[$name])) {
          throw new \Exception("Unavailable module: '$name'. If this module needs to be downloaded separately, annotate the test class with '@requires module $name'.");
        }
        $extension = $list[$name];
        $module_handler->addModule($name, $extension->getPath());
        $module_handler->load($name);
      }
    }

    $help = freelinking_help('help.page.freelinking', $routeMatch);

    $this->assertNotEmpty($help);
  }

  /**
   * Provides test parameters and expected values.
   *
   * @return array
   *   An array of test parameters.
   */
  public function hookHelpProvider() {
    return [
      [[]],
      [['advanced_help', 'advanced_help_hint']],
    ];
  }

}
