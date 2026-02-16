<?php

namespace Drupal\Tests\designs\Kernel;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the designs config schema.
 *
 * @group designs
 */
class DesignConfigSchemaTest extends KernelTestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'designs',
    'designs_test',
  ];

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfig;

  /**
   * The designs manager.
   *
   * @var \Drupal\designs\DesignManagerInterface
   */
  protected $designsManager;

  /**
   * The design settings manager.
   *
   * @var \Drupal\designs\DesignSettingManagerInterface
   */
  protected $settingsManager;

  /**
   * The design content manager.
   *
   * @var \Drupal\designs\DesignContentManagerInterface
   */
  protected $contentManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->typedConfig = \Drupal::service('config.typed');
    $this->installConfig(['designs', 'designs_test']);
  }

  /**
   * Provides test data for the testDesignsConfigSchema function.
   *
   * @return array
   *   The data sources.
   */
  public function providerTestDesignsConfigSchema() {
    return [
      [
        [
          'design' => 'no_library',
        ],
      ],
      [
        [
          'design' => 'no_library',
          'settings' => [
            'attributes' => [
              'type' => 'attributes',
              'value' => 'value',
              'existing' => TRUE,
            ],
          ],
          'content' => [
            'text1' => [
              'plugin' => 'text',
              'config' => [
                'value' => 'content',
              ],
            ],
            'token1' => [
              'plugin' => 'token',
              'config' => [
                'value' => 'token [site:name]',
              ],
            ],
            'twig1' => [
              'plugin' => 'twig',
              'config' => [
                'value' => 'twig {{ token }}',
              ],
            ],
          ],
          'regions' => [
            'hamburger' => [
              'name',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Tests the designs config schema for designs plugins.
   *
   * @dataProvider providerTestDesignsConfigSchema
   */
  public function testDesignsConfigSchema($values) {
    $config = $this->config('designs_test.settings');

    $config->set('total', $values);
    $this->assertConfigSchema($this->typedConfig, $config->getName(), $config->get());
  }

}
