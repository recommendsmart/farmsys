<?php

namespace Drupal\Tests\flow\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Tests merging field values using Flow.
 *
 * @group flow
 */
class MergeTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'field',
    'filter',
    'text',
    'node',
    'serialization',
    'flow',
    'views',
    'flow_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(static::$modules);
    User::create(['uid' => 0, 'status' => 0, 'name' => ''])->save();
    User::create(['uid' => 1, 'name' => 'admin'])->save();
  }

  /**
   * Tests merging values on nodes.
   */
  public function testMergeNode() {
    $summary = $this->randomMachineName(16);

    $node = Node::create([
      'type' => 'page',
      'tnid' => 0,
      'uid' => 0,
      'title' => 'First page',
      'body' => [['value' => 'I should be replaced by a merge.', 'summary' => $summary, 'format' => 'plain_text']],
    ]);
    $this->assertEquals('First page', (string) $node->label());
    $this->assertEquals('I should be replaced by a merge.', $node->body->value);
    $node->save();
    $this->assertEquals('First page!', (string) $node->label());
    $this->assertEquals('The "First page" got merged.', $node->body->value);
    $this->assertEquals('Merged summary value.', $node->body->summary);
    $this->assertSame((string) $node->uid->target_id, '0', "UID must not have been changed.");
  }

}
