<?php

namespace Drupal\Tests\pagerer\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Checks Pagerer functionality.
 *
 * @group Pagerer
 */
class PagererTest extends BrowserTestBase {

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
    'pagerer',
    'pagerer_example',
    'pager_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Insert 300 log messages.
    $logger = $this->container->get('logger.factory')->get('pager_test');
    for ($i = 0; $i < 300; $i++) {
      $logger->debug($this->randomString());
    }

    $this->drupalLogin($this->drupalCreateUser([
      'access site reports',
      'administer site configuration',
    ]));
  }

  /**
   * Tests Pagerer functionality.
   */
  public function testPagerer() {
    // Admin UI tests.
    $this->drupalGet($this->pagererAdmin . '/preset/add');
    $this->submitForm(['label' => 'ui_test', 'id' => 'ui_test'], 'Create');
    $this->drupalGet($this->pagererAdmin);
    $this->submitForm(['core_override_preset' => 'ui_test'], 'Save configuration');
    $styles = [
      'standard',
      'none',
      'basic',
      'progressive',
      'adaptive',
      'mini',
      'slider',
      'scrollpane',
    ];
    foreach ($styles as $style) {
      $this->drupalGet($this->pagererAdmin . '/preset/manage/ui_test');
      $this->submitForm([
        'panes_container[left][style]' => 'none',
        'panes_container[center][style]' => 'none',
        'panes_container[right][style]' => $style,
      ], 'Save');
      $this->drupalGet($this->pagererAdmin . '/preset/manage/ui_test');
      if ($style !== 'none') {
        $this->click('[id="edit-panes-container-right-actions-reset"]');
        $this->click('[id="edit-submit"]');
        $this->assertSession()->pageTextNotContains('fooxiey');
        $this->click('[id="edit-panes-container-right-actions-configure"]');
        $this->submitForm([
          'prefix_display' => '1',
          'tags_container[pages][prefix_label]' => 'fooxiey',
        ], 'Save');
        $this->assertSession()->pageTextContains('fooxiey');
      }
    }

    // Load example page.
    $this->drupalGet('pagerer/example');
  }

  /**
   * Test proper functioning of multiple pagers with overridden querystring.
   */
  public function testQuerystringOverrides() {
    // Test data.
    $test_data = [
      // With no querystring, all pagers set to first page.
      [
        'input_querystring' => '',
        'expected' => [
          'page' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?page=0%2C0%2C%2C%2C0',
          ],
          'pg_0' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?pg=0.0...0',
          ],
          'px_1' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?px=1.1...1',
          ],
        ],
      ],
      // Blanks around page numbers should not be relevant.
      [
        'input_querystring' => '?page=2  ,    10,,,   5     ,,',
        'expected' => [
          'page' => [
            'markup' => [0 => '3', 1 => '11', 4 => '6'],
            'querystring' => '?page=2%2C10%2C%2C%2C5',
          ],
          'pg_0' => [
            'markup' => [0 => '3', 1 => '11', 4 => '6'],
            'querystring' => '?pg=2.10...5',
          ],
          'px_1' => [
            'markup' => [0 => '3', 1 => '11', 4 => '6'],
            'querystring' => '?px=3.11...6',
          ],
        ],
      ],
      [
        'input_querystring' => '?pg=  2  .    10...   5     ..',
        'expected' => [
          'page' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?pg=  2  .    10...   5     ..&page=0%2C0%2C%2C%2C0',
          ],
          'pg_0' => [
            'markup' => [0 => '3', 1 => '11', 4 => '6'],
            'querystring' => '?pg=2.10...5',
          ],
          'px_1' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?pg=  2  .    10...   5     ..&px=1.1...1',
          ],
        ],
      ],
      [
        'input_querystring' => '?px=  3  .    11...   6     ..',
        'expected' => [
          'page' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?px=  3  .    11...   6     ..&page=0%2C0%2C%2C%2C0',
          ],
          'pg_0' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?px=  3  .    11...   6     ..&pg=0.0...0',
          ],
          'px_1' => [
            'markup' => [0 => '3', 1 => '11', 4 => '6'],
            'querystring' => '?px=3.11...6',
          ],
        ],
      ],
      // Blanks within page numbers should lead to only the first integer
      // to be considered.
      [
        'input_querystring' => '?page=2  ,   3 0,,,   4  13    ,,',
        'expected' => [
          'page' => [
            'markup' => [0 => '3', 1 => '4', 4 => '5'],
            'querystring' => '?page=2%2C3%2C%2C%2C4',
          ],
          'pg_0' => [
            'markup' => [0 => '3', 1 => '4', 4 => '5'],
            'querystring' => '?pg=2.3...4',
          ],
          'px_1' => [
            'markup' => [0 => '3', 1 => '4', 4 => '5'],
            'querystring' => '?px=3.4...5',
          ],
        ],
      ],
      [
        'input_querystring' => '?pg=2  .   3 0...   4  13    ..',
        'expected' => [
          'page' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?pg=2  .   3 0...   4  13    ..&page=0%2C0%2C%2C%2C0',
          ],
          'pg_0' => [
            'markup' => [0 => '3', 1 => '4', 4 => '5'],
            'querystring' => '?pg=2.3...4',
          ],
          'px_1' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?pg=2  .   3 0...   4  13    ..&px=1.1...1',
          ],
        ],
      ],
      [
        'input_querystring' => '?px=3  .   4 1...   5  14    ..',
        'expected' => [
          'page' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?px=3  .   4 1...   5  14    ..&page=0%2C0%2C%2C%2C0',
          ],
          'pg_0' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?px=3  .   4 1...   5  14    ..&pg=0.0...0',
          ],
          'px_1' => [
            'markup' => [0 => '3', 1 => '4', 4 => '5'],
            'querystring' => '?px=3.4...5',
          ],
        ],
      ],
      // If floats are passed as page numbers, only the integer part is
      // returned. NOTE - the override in Pagerer is different from core as dots
      // are interpreted as pager id separators.
      [
        'input_querystring' => '?page=2.1,6.999,,,5.',
        'expected' => [
          'page' => [
            'markup' => [0 => '3', 1 => '7', 4 => '6'],
            'querystring' => '?page=2%2C6%2C%2C%2C5',
          ],
          'pg_0' => [
            'markup' => [0 => '3', 1 => '7', 4 => '6'],
            'querystring' => '?pg=2.6...5',
          ],
          'px_1' => [
            'markup' => [0 => '3', 1 => '7', 4 => '6'],
            'querystring' => '?px=3.7...6',
          ],
        ],
      ],
      [
        'input_querystring' => '?pg=2. 6.999 . . 5.',
        'expected' => [
          'page' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?pg=2. 6.999 . . 5.&page=0%2C0%2C%2C%2C0',
          ],
          'pg_0' => [
            'markup' => [0 => '3', 1 => '7', 4 => '6'],
            'querystring' => '?pg=2.6...5',
          ],
          'px_1' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?pg=2. 6.999 . . 5.&px=1.1...1',
          ],
        ],
      ],
      [
        'input_querystring' => '?px=3. 7.999 . . 6.',
        'expected' => [
          'page' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?px=3. 7.999 . . 6.&page=0%2C0%2C%2C%2C0',
          ],
          'pg_0' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?px=3. 7.999 . . 6.&pg=0.0...0',
          ],
          'px_1' => [
            'markup' => [0 => '3', 1 => '7', 4 => '6'],
            'querystring' => '?px=3.7...6',
          ],
        ],
      ],
      [
        'input_querystring' => '?pg=2,1.6,999...5,',
        'expected' => [
          'page' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?pg=2%2C1.6%2C999...5%2C&page=0%2C0%2C%2C%2C0',
          ],
          'pg_0' => [
            'markup' => [0 => '3', 1 => '7', 4 => '6'],
            'querystring' => '?pg=2.6...5',
          ],
          'px_1' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?pg=2%2C1.6%2C999...5%2C&px=1.1...1',
          ],
        ],
      ],
      [
        'input_querystring' => '?px=2,1.6,999...5,',
        'expected' => [
          'page' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?px=2%2C1.6%2C999...5%2C&page=0%2C0%2C%2C%2C0',
          ],
          'pg_0' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?px=2%2C1.6%2C999...5%2C&pg=0.0...0',
          ],
          'px_1' => [
            'markup' => [0 => '2', 1 => '6', 4 => '5'],
            'querystring' => '?px=2.6...5',
          ],
        ],
      ],
      // Partial page fragment, undefined pagers set to first page.
      [
        'input_querystring' => '?page=5,2',
        'expected' => [
          'page' => [
            'markup' => [0 => '6', 1 => '3', 4 => '1'],
            'querystring' => '?page=5%2C2%2C%2C%2C0',
          ],
          'pg_0' => [
            'markup' => [0 => '6', 1 => '3', 4 => '1'],
            'querystring' => '?pg=5.2...0',
          ],
          'px_1' => [
            'markup' => [0 => '6', 1 => '3', 4 => '1'],
            'querystring' => '?px=6.3...1',
          ],
        ],
      ],
      [
        'input_querystring' => '?pg=5.2',
        'expected' => [
          'page' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?pg=5.2&page=0%2C0%2C%2C%2C0',
          ],
          'pg_0' => [
            'markup' => [0 => '6', 1 => '3', 4 => '1'],
            'querystring' => '?pg=5.2...0',
          ],
          'px_1' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?pg=5.2&px=1.1...1',
          ],
        ],
      ],
      [
        'input_querystring' => '?px=6.3',
        'expected' => [
          'page' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?px=6.3&page=0%2C0%2C%2C%2C0',
          ],
          'pg_0' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?px=6.3&pg=0.0...0',
          ],
          'px_1' => [
            'markup' => [0 => '6', 1 => '3', 4 => '1'],
            'querystring' => '?px=6.3...1',
          ],
        ],
      ],
      // Partial page fragment, undefined pagers set to first page.
      [
        'input_querystring' => '?page=,2',
        'expected' => [
          'page' => [
            'markup' => [0 => '1', 1 => '3', 4 => '1'],
            'querystring' => '?page=0%2C2%2C%2C%2C0',
          ],
          'pg_0' => [
            'markup' => [0 => '1', 1 => '3', 4 => '1'],
            'querystring' => '?pg=0.2...0',
          ],
          'px_1' => [
            'markup' => [0 => '1', 1 => '3', 4 => '1'],
            'querystring' => '?px=1.3...1',
          ],
        ],
      ],
      [
        'input_querystring' => '?pg=.2',
        'expected' => [
          'page' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?pg=.2&page=0%2C0%2C%2C%2C0',
          ],
          'pg_0' => [
            'markup' => [0 => '1', 1 => '3', 4 => '1'],
            'querystring' => '?pg=0.2...0',
          ],
          'px_1' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?pg=.2&px=1.1...1',
          ],
        ],
      ],
      [
        'input_querystring' => '?px=.3',
        'expected' => [
          'page' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?px=.3&page=0%2C0%2C%2C%2C0',
          ],
          'pg_0' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?px=.3&pg=0.0...0',
          ],
          'px_1' => [
            'markup' => [0 => '1', 1 => '3', 4 => '1'],
            'querystring' => '?px=1.3...1',
          ],
        ],
      ],
      // Partial page fragment, undefined pagers set to first page.
      [
        'input_querystring' => '?page=,',
        'expected' => [
          'page' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?page=0%2C0%2C%2C%2C0',
          ],
          'pg_0' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?pg=0.0...0',
          ],
          'px_1' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?px=1.1...1',
          ],
        ],
      ],
      [
        'input_querystring' => '?pg=.',
        'expected' => [
          'page' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?pg=.&page=0%2C0%2C%2C%2C0',
          ],
          'pg_0' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?pg=0.0...0',
          ],
          'px_1' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?pg=.&px=1.1...1',
          ],
        ],
      ],
      [
        'input_querystring' => '?px=.',
        'expected' => [
          'page' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?px=.&page=0%2C0%2C%2C%2C0',
          ],
          'pg_0' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?px=.&pg=0.0...0',
          ],
          'px_1' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?px=1.1...1',
          ],
        ],
      ],
      // With overflow pages, all pagers set to max page.
      [
        'input_querystring' => '?page=99,99,,,99',
        'expected' => [
          'page' => [
            'markup' => [0 => '16', 1 => '16', 4 => '16'],
            'querystring' => '?page=15%2C15%2C%2C%2C15',
          ],
          'pg_0' => [
            'markup' => [0 => '16', 1 => '16', 4 => '16'],
            'querystring' => '?pg=15.15...15',
          ],
          'px_1' => [
            'markup' => [0 => '16', 1 => '16', 4 => '16'],
            'querystring' => '?px=16.16...16',
          ],
        ],
      ],
      [
        'input_querystring' => '?pg=99.99...99',
        'expected' => [
          'page' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?pg=99.99...99&page=0%2C0%2C%2C%2C0',
          ],
          'pg_0' => [
            'markup' => [0 => '16', 1 => '16', 4 => '16'],
            'querystring' => '?pg=15.15...15',
          ],
          'px_1' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?pg=99.99...99&px=1.1...1',
          ],
        ],
      ],
      [
        'input_querystring' => '?px=99.99...99',
        'expected' => [
          'page' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?px=99.99...99&page=0%2C0%2C%2C%2C0',
          ],
          'pg_0' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?px=99.99...99&pg=0.0...0',
          ],
          'px_1' => [
            'markup' => [0 => '16', 1 => '16', 4 => '16'],
            'querystring' => '?px=16.16...16',
          ],
        ],
      ],
      // Wrong value for the page resets pager to first page.
      [
        'input_querystring' => '?page=bar,5,foo,qux,bet',
        'expected' => [
          'page' => [
            'markup' => [0 => '1', 1 => '6', 4 => '1'],
            'querystring' => '?page=0%2C5%2C%2C%2C0',
          ],
          'pg_0' => [
            'markup' => [0 => '1', 1 => '6', 4 => '1'],
            'querystring' => '?pg=0.5...0',
          ],
          'px_1' => [
            'markup' => [0 => '1', 1 => '6', 4 => '1'],
            'querystring' => '?px=1.6...1',
          ],
        ],
      ],
      [
        'input_querystring' => '?pg=bar.5.foo.qux.bet',
        'expected' => [
          'page' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?pg=bar.5.foo.qux.bet&page=0%2C0%2C%2C%2C0',
          ],
          'pg_0' => [
            'markup' => [0 => '1', 1 => '6', 4 => '1'],
            'querystring' => '?pg=0.5...0',
          ],
          'px_1' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?pg=bar.5.foo.qux.bet&px=1.1...1',
          ],
        ],
      ],
      [
        'input_querystring' => '?px=bar.6.foo.qux.bet',
        'expected' => [
          'page' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?px=bar.6.foo.qux.bet&page=0%2C0%2C%2C%2C0',
          ],
          'pg_0' => [
            'markup' => [0 => '1', 1 => '1', 4 => '1'],
            'querystring' => '?px=bar.6.foo.qux.bet&pg=0.0...0',
          ],
          'px_1' => [
            'markup' => [0 => '1', 1 => '6', 4 => '1'],
            'querystring' => '?px=1.6...1',
          ],
        ],
      ],
    ];

    // Loop through test sets.
    foreach (['page', 'pg_0', 'px_1'] as $set) {
      $match_querystring = 'expected_querystring_' . $set;
      switch ($set) {
        case 'core':
          // Run with default: 'page' and 0-based page numbers.
          // Using 'page', it works.
          // Using 'pg' or 'px', pager is not initialised.
          break;

        case 'pg_0':
          // Override: 'pg' and 0-based page numbers.
          // Using 'page' or 'pg', it works.
          // Using 'px', pager is not initialised.
          $this->drupalGet($this->pagererAdmin . '/url_settings');
          $this->submitForm(['core_override_querystring' => TRUE], 'Save configuration');
          break;

        case 'px_1':
          // Override: 'px' and 1-based page numbers.
          // Using 'page' or 'px', it works.
          // Using 'pg', pager is not initialised.
          $this->drupalGet($this->pagererAdmin . '/url_settings');
          $this->submitForm(['index_base' => 1, 'querystring_key' => 'px'], 'Save configuration');
          break;

      }
      foreach ($test_data as $data) {
        $input_query = str_replace(' ', '%20', $data['input_querystring']);
        $expected_markup = $data['expected'][$set]['markup'];
        $this->drupalGet($GLOBALS['base_root'] . '/pager-test/multiple-pagers' . $input_query, ['external' => TRUE]);
        foreach (array_keys($expected_markup) as $pager_element) {
          $active_page = $this->cssSelect("div.test-pager-{$pager_element} ul.pager__items li.is-active:contains('{$expected_markup[$pager_element]}')");
          $this->assertNotEmpty($active_page, $data['input_querystring'] . " // $pager_element // $set");
          $destination = $active_page[0]->find('css', 'a')->getAttribute('href');
          $destination = str_replace('%20', ' ', $destination);
          $this->assertEquals($data['expected'][$set]['querystring'], $destination, $data['input_querystring'] . " // $pager_element // $set");
        }
      }
    }
  }

  /**
   * Test proper functioning of multiple adaptive keys pagers.
   */
  public function testAdaptiveKeysQuerystring() {
    // Add more entries in the log.
    for ($i = 0; $i < 2700; $i++) {
      $this->container->get('logger.factory')->get('pager_test')->debug($this->randomString());
    }

    // Setup the core overriden pager to Adaptive without breakers.
    $this->drupalGet($this->pagererAdmin . '/preset/add');
    $this->submitForm(['label' => 'ui_test', 'id' => 'ui_test'], 'Create');
    $this->drupalGet($this->pagererAdmin);
    $this->submitForm(['core_override_preset' => 'ui_test'], 'Save configuration');
    $this->drupalGet($this->pagererAdmin . '/preset/manage/ui_test');
    $this->submitForm([
      'panes_container[left][style]' => 'none',
      'panes_container[center][style]' => 'adaptive',
      'panes_container[right][style]' => 'none',
    ], 'Save');
    $this->drupalGet($this->pagererAdmin . '/preset/manage/ui_test');
    $this->click('[id="edit-panes-container-center-actions-configure"]');
    $this->submitForm(['breaker_display' => FALSE], 'Save');

    // Test data.
    $test_data = [
      [
        'pager_id' => 0,
        'pager_item' => 7,
        'expected_querystring_core' => '?page=75%2C0%2C%2C%2C0&page_ak=37.150',
        'expected_querystring_pg_0' => '?pg=75.0...0-ak-37_150',
        'expected_querystring_px_1' => '?px=76.1...1-ak-38_151',
      ],
      [
        'pager_id' => 0,
        'pager_item' => 5,
        'expected_querystring_core' => '?page=74%2C0%2C%2C%2C0&page_ak=37.150.75',
        'expected_querystring_pg_0' => '?pg=74.0...0-ak-37_150_75',
        'expected_querystring_px_1' => '?px=75.1...1-ak-38_151_76',
      ],
      [
        'pager_id' => 0,
        'pager_item' => 7,
        'expected_querystring_core' => '?page=75%2C0%2C%2C%2C0&page_ak=37.150.75',
        'expected_querystring_pg_0' => '?pg=75.0...0-ak-37_150_75',
        'expected_querystring_px_1' => '?px=76.1...1-ak-38_151_76',
      ],
      [
        'pager_id' => 0,
        'pager_item' => 7,
        'expected_querystring_core' => '?page=76%2C0%2C%2C%2C0&page_ak=37.150.75',
        'expected_querystring_pg_0' => '?pg=76.0...0-ak-37_150_75',
        'expected_querystring_px_1' => '?px=77.1...1-ak-38_151_76',
      ],
      [
        'pager_id' => 0,
        'pager_item' => 7,
        'expected_querystring_core' => '?page=77%2C0%2C%2C%2C0&page_ak=37.150.75',
        'expected_querystring_pg_0' => '?pg=77.0...0-ak-37_150_75',
        'expected_querystring_px_1' => '?px=78.1...1-ak-38_151_76',
      ],
      [
        'pager_id' => 0,
        'pager_item' => 9,
        'expected_querystring_core' => '?page=93%2C0%2C%2C%2C0&page_ak=79.112',
        'expected_querystring_pg_0' => '?pg=93.0...0-ak-79_112',
        'expected_querystring_px_1' => '?px=94.1...1-ak-80_113',
      ],
      [
        'pager_id' => 1,
        'pager_item' => 3,
        'expected_querystring_core' => '?page=93%2C2%2C%2C%2C0&page_ak=79.112',
        'expected_querystring_pg_0' => '?pg=93.2...0-ak-79_112',
        'expected_querystring_px_1' => '?px=94.3...1-ak-80_113',
      ],
      [
        'pager_id' => 4,
        'pager_item' => 8,
        'expected_querystring_core' => '?page=93%2C2%2C%2C%2C150&page_ak=79.112',
        'expected_querystring_pg_0' => '?pg=93.2...150-ak-79_112',
        'expected_querystring_px_1' => '?px=94.3...151-ak-80_113',
      ],
      [
        'pager_id' => 4,
        'pager_item' => 2,
        'expected_querystring_core' => '?page=93%2C2%2C%2C%2C75&page_ak=79.112%2C%2C%2C%2C0.113',
        'expected_querystring_pg_0' => '?pg=93.2...75-ak-79_112....0_113',
        'expected_querystring_px_1' => '?px=94.3...76-ak-80_113....1_114',
      ],
    ];

    // Loop through test sets.
    foreach (['core', 'pg_0', 'px_1'] as $set) {
      $match_querystring = 'expected_querystring_' . $set;
      switch ($set) {
        case 'core':
          break;

        case 'pg_0':
          // Override: 'pg' and 0-based page numbers.
          $this->drupalGet($this->pagererAdmin . '/url_settings');
          $this->submitForm([
            'core_override_querystring' => TRUE,
          ], 'Save configuration');
          break;

        case 'px_1':
          // Override: 'px' and 1-based page numbers.
          $this->drupalGet($this->pagererAdmin . '/url_settings');
          $this->submitForm([
            'index_base' => 1,
            'querystring_key' => 'px',
          ], 'Save configuration');
          break;

      }

      // First page.
      $this->drupalGet('pager-test/multiple-pagers');

      // Loop through test pager clicks.
      foreach ($test_data as $id => $test) {
        $elements = $this->xpath('//div[contains(@class, :pager)]//ul[contains(@class, :items)]/li[' . $test['pager_item'] . ']/a', [
          ':pager' => 'test-pager-' . $test['pager_id'],
          ':items' => 'pager__items',
        ]);
        $this->assertStringContainsString($test[$match_querystring], $elements[0]->getAttribute('href'), "Test $id");
        $elements[0]->click();
        $this->assertStringContainsString('pager-test/multiple-pagers' . $test[$match_querystring], $this->getUrl(), "Test $id");
      }
    }
  }

}
