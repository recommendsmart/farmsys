<?php

namespace Drupal\Tests\yasm_blocks\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Provides a class for Yasm charts functional tests.
 *
 * @group yasm
 */
class YasmBlocksTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['yasm_blocks'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * Admin users with all permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $yasmUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create yasm user.
    $this->yasmUser = $this->drupalCreateUser([
      'view the administration theme',
      'access administration pages',
      'administer blocks',
    ]);
  }

  /**
   * Tests yasm blocks.
   */
  public function testsYasmBlocks() {
    $this->drupalLogin($this->yasmUser);

    $this->drupalGet('admin/structure/block/library/stable');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('YASM site counts');
    $this->assertSession()->responseContains('YASM current user counts');
    $this->assertSession()->responseContains('YASM groups counts');

    $theme = \Drupal::service('theme_handler')->getDefault();
    $blocks = ['yasm_block_user', 'yasm_block_site', 'yasm_block_group'];
    foreach ($blocks as $block) {
      $this->drupalGet("admin/structure/block/add/$block/$theme");
      $this->assertSession()->statusCodeEquals(200);
    }
  }

}
