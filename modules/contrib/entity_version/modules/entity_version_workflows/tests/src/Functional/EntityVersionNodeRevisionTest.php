<?php

namespace Drupal\Tests\entity_version_workflows\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\entity_version\Traits\EntityVersionAssertionsTrait;

/**
 * Tests the node revisions work with entity versions.
 */
class EntityVersionNodeRevisionTest extends BrowserTestBase {

  use EntityVersionAssertionsTrait;

  /**
   * Admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path',
    'node',
    'user',
    'system',
    'workflows',
    'content_moderation',
    'entity_version',
    'entity_version_workflows',
    'entity_version_workflows_example',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer nodes',
    ]);

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that when reverting nodes, we don't update the version.
   */
  public function testNodeRevisionRevert(): void {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $values = [
      'title' => 'Workflow node',
      'type' => 'entity_version_workflows_example',
      'moderation_state' => 'draft',
    ];
    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->create($values);
    $node->save();
    // Revision 1.
    $this->assertEntityVersion($node, 0, 0, 0);

    // Bump up the version.
    $node->set('title', 'New title');
    $node->save();
    // Revision 2.
    $this->assertEntityVersion($node, 0, 0, 1);

    // Bump up the version again.
    $node->set('title', 'Newer title');
    $node->save();
    // Revision 3.
    $this->assertEntityVersion($node, 0, 0, 2);

    // We should have 3 revisions in total.
    $this->assertCount(3, $node_storage->revisionIds($node));

    // Revert to the second revision and assert its version matches.
    $url = Url::fromRoute('node.revision_revert_confirm', [
      'node' => $node->id(),
      'node_revision' => 2,
    ]);
    $this->drupalGet($url);
    $this->submitForm([], 'Revert');

    $node_storage->resetCache();
    $node = $node_storage->load($node->id());
    $this->assertEquals(4, $node->getRevisionId());
    $this->assertEntityVersion($node, 0, 0, 1);
  }

}
