<?php

namespace Drupal\Tests\entity_inherit\Unit\Plugin\EntityInheritPlugin;

use Drupal\entity_inherit\Plugin\EntityInheritPlugin\EntityInheritRemoveSystemFields;
use Drupal\Tests\entity_inherit\Unit\EntityInheritTestBase;

/**
 * Test EntityInheritRemoveSystemFields.
 *
 * @group entity_inherit
 */
class EntityInheritRemoveSystemFieldsTest extends EntityInheritTestBase {

  /**
   * Test for filterFields().
   *
   * @param string $message
   *   The test message.
   * @param array $input
   *   The input.
   * @param array $expected
   *   The exception result.
   *
   * @cover ::filterFields
   * @dataProvider providerFilterFields
   */
  public function testFilterFields(string $message, array $input, array $expected) {
    $object = $this->getMockBuilder(EntityInheritRemoveSystemFields::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $output = $input;
    $object->filterFields($output, $input, 'ignored', $this->mockApp());

    if ($output != $expected) {
      print_r([
        'output' => $output,
        'expected' => $expected,
      ]);
    }

    $this->assertTrue($output == $expected, $message);
  }

  /**
   * Provider for testFilterFields().
   */
  public function providerFilterFields() {
    return [
      [
        'message' => 'Empty',
        'input' => [],
        'expected' => [],
      ],
      [
        'message' => 'Empty',
        'input' => [
          'whatever' => [],
          'node.body' => [],
          'node.not_body' => [],
          'node.field_x' => [],
          'whatever.field_x' => [],
          'whatever.does_not_start_with_field' => [],
          'whatever' => [],
          'whatever.field_999____' => [],
        ],
        'expected' => [
          'node.body' => [],
          'node.field_x' => [],
          'whatever.field_x' => [],
          'whatever.field_999____' => [],
        ],
      ],
    ];
  }

}
