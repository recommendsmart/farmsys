<?php

namespace Drupal\Tests\widget_type\Functional;

use Behat\Mink\Element\NodeElement;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the InteractiveComponents.
 *
 * @group widget_type
 * @coversDefaultClass \Drupal\widget_type\Controller\InteractiveComponents
 */
class InteractiveComponentsTest extends BrowserTestBase {

  /**
   * The theme to install as the default for testing.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['widget_type'];

  /**
   * @covers ::__invoke
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  public function testInvoke() {
    $this->drupalLogin($this->createUser([
      'administer widget_type entities',
    ]));
    $this->drupalGet(Url::fromRoute('interactive_components'));
    $session = $this->getSession();
    $page = $session->getPage();
    self::assertSame(200, $session->getStatusCode());
    $elements = (array) $page->findAll('css', 'li');
    $texts = array_map(function (NodeElement $element) {
      return $element->getText();
    }, $elements);
    self::assertEquals(['Widget Types'], $texts);
    $elements = (array) $page->findAll('css', 'p');
    self::assertCount(3, $elements);
  }

}
