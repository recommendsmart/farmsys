<?php

namespace Drupal\Tests\field_fallback\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the FieldFallbackConverterManager class.
 *
 * @group field_fallback
 *
 * @coversDefaultClass \Drupal\field_fallback\Plugin\FieldFallbackConverterManager
 */
class FieldFallbackConverterPluginManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_fallback_test',
    'field_fallback',
  ];

  /**
   * The field fallback converter manager.
   *
   * @var \Drupal\field_fallback\Plugin\FieldFallbackConverterManagerInterface
   */
  protected $fieldFallbackConverterManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fieldFallbackConverterManager = $this->container->get('plugin.manager.field_fallback_converter');
  }

  /**
   * Tests the :getDefinitionsBySourceAndTarget method.
   *
   * @dataProvider getDefinitionsBySourceAndTargetDataProvider
   *
   * @covers ::getDefinitionsBySourceAndTarget
   */
  public function testGetDefinitionsBySourceAndTarget(string $source, string $target, array $expected) {
    $definitions = $this->fieldFallbackConverterManager->getDefinitionsBySourceAndTarget($source, $target);
    $this->assertEquals($expected, array_keys($definitions));
  }

  /**
   * Test the getDefinitionsBySourceAndTargetDataProvider method.
   *
   * @return array
   *   An array of test cases, each which the following values:
   *   - an array of parameters passed to getDefinitionsBySourceAndTarget.
   *   - The keys of the found plugin definitions.
   */
  public function getDefinitionsBySourceAndTargetDataProvider(): array {
    return [
      [
        'string',
        'string',
        [
          'default',
          'string_to_any_value',
          'static_string',
        ],
      ],
      [
        'image',
        'string',
        [
          'default',
          'image_to_string',
          'static_string',
        ],
      ],
      [
        'image',
        'image',
        [
          'default',
        ],
      ],
      [
        'string',
        'image',
        [
          'default',
          'string_to_any_value',
        ],
      ],
    ];
  }

  /**
   * Test that creating a plugin works and retrieving the configured values.
   *
   * @dataProvider createInstanceDataProvider
   */
  public function testCreateInstance(string $plugin_id, array $source, array $target, int $weight) {
    /** @var \Drupal\field_fallback\Plugin\FieldFallbackConverterInterface $plugin */
    $plugin = $this->fieldFallbackConverterManager->createInstance($plugin_id);
    $this->assertEquals($source, $plugin->getSource());
    $this->assertEquals($target, $plugin->getTarget());
    $this->assertEquals($weight, $plugin->getWeight());
  }

  /**
   * Test the createInstance method.
   *
   * @return array
   *   An array of test cases, each which the following values:
   *   - an array of parameters passed to createInstance.
   *   - The keys of the found plugin definitions.
   */
  public function createInstanceDataProvider(): array {
    return [
      [
        'string_to_any_value',
        ['string'],
        ['*'],
        0,
      ],
      [
        'image_to_string',
        ['image'],
        ['string'],
        1,
      ],
      [
        'static_string',
        ['*'],
        ['string'],
        2,
      ],
      [
        'default',
        ['*'],
        ['*'],
        -999,
      ],
    ];
  }

  /**
   * Tests the :getAvailableSourcesByTarget method.
   *
   * @dataProvider getAvailableSourcesByTargetDataProvider
   *
   * @covers ::getAvailableSourcesByTarget
   */
  public function testGetAvailableSourcesByTarget(string $target, array $expected) {
    $result = $this->fieldFallbackConverterManager->getAvailableSourcesByTarget($target);
    $this->assertEquals($expected, $result);
  }

  /**
   * Test the getAvailableSourcesByTarget method.
   *
   * @return array
   *   An array of test cases, each which the following values:
   *   - an array of parameters passed to getAvailableSourcesByTarget.
   *   - The found sources.
   */
  public function getAvailableSourcesByTargetDataProvider(): array {
    return [
      [
        'image',
        ['string'],
      ],
      [
        'text_long',
        [
          'string',
          'entity_reference_revisions',
        ],
      ],
    ];
  }

  /**
   * Tests the :getAvailableTargetsBySource method.
   *
   * @dataProvider getAvailableTargetsBySourceDataProvider
   *
   * @covers ::getAvailableTargetsBySource
   */
  public function testGetAvailableTargetsBySource(string $source, array $expected) {
    $result = $this->fieldFallbackConverterManager->getAvailableTargetsBySource($source);
    $this->assertEquals($expected, $result);
  }

  /**
   * Test the getAvailableTargetsBySource method.
   *
   * @return array
   *   An array of test cases, each which the following values:
   *   - an array of parameters passed to getAvailableTargetsBySource.
   *   - The found targets.
   */
  public function getAvailableTargetsBySourceDataProvider(): array {
    return [
      [
        'image',
        ['string'],
      ],
      [
        'string',
        ['string'],
      ],
      [
        'string',
        ['string'],
      ],
      [
        'entity_reference_revisions',
        [
          'string',
          'text_long',
        ],
      ],
    ];
  }

}
