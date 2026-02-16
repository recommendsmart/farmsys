<?php

namespace Drupal\Tests\flow\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\flow\Entity\Flow;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;

/**
 * Tests relating content using Flow.
 *
 * @group flow
 */
class RelateTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'field',
    'filter',
    'text',
    'node',
    'serialization',
    'flow',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(static::$modules);
    User::create(['uid' => 0, 'status' => 0, 'name' => ''])->save();
    User::create(['uid' => 1, 'name' => 'admin'])->save();

    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
      'new_revision' => TRUE,
    ]);
    $node_type->save();
  }

  /**
   * Tests relating content.
   */
  public function testRelateContent() {
    // Create a reference field.
    $field_definition = FieldStorageConfig::create([
      'field_name' => 'field_other_content',
      'type' => 'entity_reference',
      'entity_type' => 'node',
      'settings' => [
        'target_type' => 'node',
      ],
      'cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED,
    ]);
    $field_definition->save();
    $field = FieldConfig::create([
      'field_storage' => $field_definition,
      'label' => 'Other content',
      'entity_type' => 'node',
      'bundle' => 'article',
    ]);
    $field->save();

    $node1 = Node::create([
      'type' => 'article',
      'uid' => 1,
      'status' => 1,
      'title' => 'My first article',
    ]);
    $node1->save();
    $node2 = Node::create([
      'type' => 'article',
      'uid' => 1,
      'status' => 1,
      'title' => 'My second article',
    ]);
    $node2->save();
    $this->assertNull($node1->field_other_content->target_id);
    $this->assertNull($node2->field_other_content->target_id);

    // Create a Flow configuration for relating content.
    Flow::create([
      'langcode' => 'en',
      'status' => TRUE,
      'id' => 'node.article.save',
      'targetEntityType' => 'node',
      'targetBundle' => 'article',
      'taskMode' => 'save',
      'tasks' => [
        [
          'id' => 'relate:node.article::node.article',
          'type' => 'relate',
          'weight' => 0,
          'active' => TRUE,
          'execution' => ['start' => 'now'],
          'subject' => [
            'id' => 'save:node.article',
            'type' => 'save',
            'settings' => [],
            'third_party_settings' => [],
          ],
          'settings' => [
            'field_name' => 'field_other_content',
            'method' => 'set:clear',
            'reverse' => [
              'field_name' => 'field_other_content',
              'method' => 'set:clear',
            ],
            'target' => [
              'id' => 'load:node.article',
              'type' => 'load',
              'settings' => [
                'mode' => 'id',
                'entity_id' => (string) $node2->id(),
                'entity_uuid' => NULL,
                'view' => [
                  'id' => NULL,
                  'display' => NULL,
                  'arguments' => NULL,
                ],
                'fallback' => ['method' => 'nothing'],
              ],
            ],
          ],
          'third_party_settings' => [],
        ],
      ],
    ])
    ->save();

    $node1->save();
    $node1 = Node::load($node1->id());
    $node2 = Node::load($node2->id());

    $this->assertCount(1, $node1->field_other_content);
    $this->assertCount(1, $node2->field_other_content);
    $this->assertSame((string) $node2->id(), (string) $node1->field_other_content->target_id);
    $this->assertSame((string) $node1->id(), (string) $node2->field_other_content->target_id);
  }

}
