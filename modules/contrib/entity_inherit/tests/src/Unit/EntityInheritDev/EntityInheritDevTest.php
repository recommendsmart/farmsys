<?php

namespace Drupal\Tests\entity_inherit\Unit\EntityInheritDev;

use Drupal\entity_inherit\EntityInheritDev\EntityInheritDev;
use PHPUnit\Framework\TestCase;

/**
 * Test EntityInheritDev.
 *
 * @group entity_inherit
 */
class EntityInheritDevTest extends TestCase {

  /**
   * Test for formatParents().
   *
   * @param string $message
   *   The test message.
   * @param array $input
   *   The mock input.
   * @param array $expected
   *   The expected output.
   *
   * @cover ::formatParents
   * @dataProvider providerFormatParents
   */
  public function testFormatParents(string $message, array $input, array $expected) {
    $object = $this->getMockBuilder(EntityInheritDev::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $output = $object->formatParents($input);

    if ($output != $expected) {
      print_r([
        'message' => $message,
        'output' => $output,
        'expected' => $expected,
      ]);
    }

    $this->assertTrue($output == $expected, $message);
  }

  /**
   * Provider for testFormatParents().
   */
  public function providerFormatParents() {
    return [
      [
        'message' => 'Empty',
        'input' => [],
        'expected' => [],
      ],
      [
        'message' => 'Not empty',
        'input' => [1, 2, 3],
        'expected' => [
          [
            'target_id' => 1,
          ],
          [
            'target_id' => 2,
          ],
          [
            'target_id' => 3,
          ],
        ],
      ],
    ];
  }

}
