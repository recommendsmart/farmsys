<?php

namespace Drupal\Tests\pagerer\Functional;

use Drupal\Tests\comment\Functional\CommentInterfaceTest;

/**
 * Test replacement of Drupal core pager for Comment interface.
 *
 * @group Pagerer
 */
class CorePagerReplaceCommentInterfaceTest extends CommentInterfaceTest {

  /**
   * The URL for Pagerer admin UI page.
   *
   * @var string
   */
  protected $pagererAdmin = 'admin/config/user-interface/pagerer';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['dblog', 'pagerer', 'comment'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'access site reports',
      'administer site configuration',
    ]));

    // Replace the core pager.
    $this->drupalGet($this->pagererAdmin . '/preset/add');
    $this->submitForm(['label' => 'core_replace', 'id' => 'core_replace'], 'Create');
    $this->drupalGet($this->pagererAdmin);
    $this->submitForm(['core_override_preset' => 'core_replace'], 'Save configuration');

    $this->drupalLogout();
  }

}
