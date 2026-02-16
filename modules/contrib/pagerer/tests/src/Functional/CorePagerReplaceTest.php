<?php

namespace Drupal\Tests\pagerer\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Pager\PagerTest;

/**
 * Test replacement of Drupal core pager.
 *
 * @group Pagerer
 */
class CorePagerReplaceTest extends PagerTest {

  /**
   * The URL for Pagerer admin UI page.
   *
   * @var string
   */
  protected $pagererAdmin = 'admin/config/user-interface/pagerer';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'dblog',
    'image',
    'pager_test',
    'pagerer',
    'pagerer_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    BrowserTestBase::setUp();

    // Insert 300 log messages.
    $logger = $this->container->get('logger.factory')->get('pager_test');
    for ($i = 0; $i < 300; $i++) {
      $logger->debug($this->randomString());
    }

    $this->drupalLogin($this->drupalCreateUser([
      'access site reports',
      'administer image styles',
      'administer site configuration',
    ]));

    // Replace the core pager.
    $this->drupalGet($this->pagererAdmin . '/preset/add');
    $this->submitForm(['label' => 'core_replace', 'id' => 'core_replace'], 'Create');
    $this->drupalGet($this->pagererAdmin);
    $this->submitForm(['core_override_preset' => 'core_replace'], 'Save configuration');
  }

  /**
   * Test that pagerer specific cache tags have been added.
   */
  public function testPagerQueryParametersAndCacheContext() {
    parent::testPagerQueryParametersAndCacheContext();
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:pagerer.settings');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:pagerer.preset.core_replace');
  }

}
