<?php

namespace Drupal\Tests\designs\Functional;

use Drupal\designs\DesignSourceManagerInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the design form.
 *
 * @group designs
 */
class FormTest extends BrowserTestBase {

  /**
   * The theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stable';

  /**
   * The modules.
   *
   * @var array
   */
  protected static $modules = [
    'designs',
    'designs_test',
  ];

  /**
   * The design source manager.
   *
   * @var \Drupal\designs\DesignSourceManagerInterface
   */
  protected $sourceManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->sourceManager = $this->container->get('plugin.manager.design_source');
  }

  /**
   * Provides testing against different types of outcomes.
   *
   * @return \string[][]
   *   The data sources.
   */
  public function providerTestSource() {
    return [
      ['a[b]', 'a[b]', 'designs_test_none'],
      ['a[b]', 'a[b]', 'designs_test_custom'],
      ['a[b]', 'a[b]', 'designs_test_all'],
      ['a[b]', 'a[c][c]', 'designs_test_none'],
      ['a[b]', 'a[c][c]', 'designs_test_custom'],
      ['a[b]', 'a[c][c]', 'designs_test_all'],
      ['a[c][c]', 'a[b]', 'designs_test_none'],
      ['a[c][c]', 'a[b]', 'designs_test_custom'],
      ['a[c][c]', 'a[b]', 'designs_test_all'],
    ];
  }

  protected function getBaseResult() {
    return [
      'design' => 'library',
      'settings' => [
        'a' => [
          'type' => 'test_setting',
          'test_local' => '',
          'test_global' => '',
        ],
      ],
    ];
  }

  /**
   * Test a combination with sources.
   *
   * @dataProvider providerTestSource
   */
  public function testSource($parents, $array_parents, $source_id) {
    $button = trim(preg_replace('/[^\w]+/', '-', $parents), '-');

    $plugin = $this->sourceManager->createInstance($source_id);

    // Check simple submit generates the appropriate form output.
    $this->drupalGet("designs_test_form/{$parents}/{$array_parents}/{$source_id}");
    $this->submitForm([], 'Submit');
    $this->assertSession()->pageTextContains(json_encode(['design' => '']));

    // The outcome revolves around this, so use accordingly.
    $result = $this->getBaseResult();
    if ($plugin->usesCustomContent()) {
      $result['content'] = [];
    }
    if ($plugin->usesRegionsForm()) {
      $result['regions']['hamburger'] = [];
    }

    // Change to the library design.
    $this->drupalGet("designs_test_form/{$parents}/{$array_parents}/{$source_id}");
    $this->submitForm(["{$parents}[design]" => 'library'], 'Change design');
    $this->submitForm([], 'Submit');
    $this->assertSession()->pageTextContains(json_encode($result));

    // Passed global value.
    $this->drupalGet("designs_test_form/{$parents}/{$array_parents}/{$source_id}");
    $this->submitForm(["{$parents}[design]" => 'library'], 'Change design');
    $this->submitForm([
      "{$parents}[settings][a][test_global]" => 'test',
    ], 'Submit');

    $outcome = $result;
    $outcome['settings']['a']['test_global'] = 'test-test';
    $this->assertSession()->pageTextContains(json_encode($outcome));

    // Change to the library design but with invalid global setting value.
    $this->drupalGet("designs_test_form/{$parents}/{$array_parents}/{$source_id}");
    $this->submitForm(["{$parents}[design]" => 'library'], 'Change design');
    $this->submitForm([
      "{$parents}[settings][a][test_global]" => 'fail',
    ], 'Submit');
    $this->assertSession()->pageTextContains('Test global was a failure.');

    // Test global validation failure with content plugin.
    $this->drupalGet("designs_test_form/{$parents}/{$array_parents}/{$source_id}");
    $this->submitForm(["{$parents}[design]" => 'library'], 'Change design');
    $this->submitForm([
      "{$parents}[settings][a][plugin]" => 'text',
    ], "{$button}-settings-a-submit");
    $this->submitForm([
      "{$parents}[settings][a][test_global]" => 'fail',
    ], 'Submit');
    $this->assertSession()->pageTextContains('Test global was a failure.');

    // Change to the library design but with invalid local setting value.
    $this->drupalGet("designs_test_form/{$parents}/{$array_parents}/{$source_id}");
    $this->submitForm(["{$parents}[design]" => 'library'], 'Change design');
    $this->submitForm([
      "{$parents}[settings][a][test_local]" => 'fail',
    ], 'Submit');
    $this->assertSession()->pageTextContains('Test local was a failure.');

    // Check validation failure for content plugin within settings plugin.
    $this->drupalGet("designs_test_form/{$parents}/{$array_parents}/{$source_id}");
    $this->submitForm(["{$parents}[design]" => 'library'], 'Change design');
    $this->submitForm([
      "{$parents}[settings][a][plugin]" => 'test_content',
    ], "{$button}-settings-a-submit");
    $this->submitForm([
      "{$parents}[settings][a][config][test1]" => 'fail',
    ], 'Submit');
    $this->assertSession()->pageTextContains('Test 1 was a failure.');

    // Change the setting contents plugin.
    $this->drupalGet("designs_test_form/{$parents}/{$array_parents}/{$source_id}");
    $this->submitForm(["{$parents}[design]" => 'library'], 'Change design');
    $this->submitForm([
      "{$parents}[settings][a][test_local]" => 'test',
      "{$parents}[settings][a][plugin]" => 'test_content',
    ], "{$button}-settings-a-submit");
    $this->submitForm([
      "{$parents}[settings][a][config][test1]" => 'test',
      "{$parents}[settings][a][config][test2]" => 'test',
    ], 'Submit');

    $outcome = $result;
    $outcome['settings']['a'] = [
      'type' => 'test_setting',
      'plugin' => 'test_content',
      'config' => [
        'test1' => 'test',
        'test2' => 'test-test',
      ],
      'test_global' => '',
      // Due to being added via defaultConfiguration.
      'test_local' => '',
    ];
    $this->assertSession()->pageTextContains(json_encode($outcome));

    // Test local validation failure.
    $this->drupalGet("designs_test_form/{$parents}/{$array_parents}/{$source_id}");
    $this->submitForm(["{$parents}[design]" => 'library'], 'Change design');
    $this->submitForm([
      "{$parents}[settings][a][plugin]" => 'text',
    ], "{$button}-settings-a-submit");

    // Add custom content.
    if ($plugin->usesCustomContent()) {
      $this->drupalGet("designs_test_form/{$parents}/{$array_parents}/{$source_id}");
      $this->submitForm(["{$parents}[design]" => 'library'], 'Change design');
      // Attempt to submit with no machine name specified.
      $this->submitForm([], "{$button}-content-addition-create");
      $this->assertSession()->pageTextContains('Label can not be empty.');
      $this->assertSession()->pageTextContains('Machine name can not be empty.');

      // Attempt to submit with machine name invalid.
      $this->submitForm([
        "{$button}-content-addition-label" => 'test',
        "{$button}-content-addition-machine" => 'test&%',
      ], "{$button}-content-addition-create");
      $this->assertSession()->pageTextContains('The machine-readable name must contain only lowercase letters, numbers, and underscores.');

      // Create the content.
      $this->submitForm([
        "{$button}-content-addition-label" => 'test',
        "{$button}-content-addition-machine" => 'test',
      ], "{$button}-content-addition-create");

      // Submit again to check for existing machine name.
      $this->submitForm([
        "{$button}-content-addition-label" => 'test',
        "{$button}-content-addition-machine" => 'test',
      ], "{$button}-content-addition-create");
      $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');

      // Final submission to check outcome.
      $this->submitForm([
        "{$button}-content-addition-label" => '',
        "{$button}-content-addition-machine" => '',
      ], 'Submit');

      $outcome = $result;
      $outcome['content']['test'] = [
        'plugin' => 'text',
        'config' => [
          'value' => '',
          'label' => 'test',
        ],
      ];
      $this->assertSession()->pageTextContains(json_encode($outcome));

      // Check validation on content plugin for contents form.
      $this->drupalGet("designs_test_form/{$parents}/{$array_parents}/{$source_id}");
      $this->submitForm(["{$parents}[design]" => 'library'], 'Change design');
      $this->submitForm([
        "{$button}-content-addition-label" => 'test',
        "{$button}-content-addition-machine" => 'test',
      ], "{$button}-content-addition-create");
      $this->submitForm([
        "{$parents}[content][test][plugin]" => 'test_content',
      ], "{$button}-content-test-submit");
      $this->submitForm([
        "{$parents}[content][test][config][test1]" => 'fail',
      ], 'Submit');
      $this->assertSession()->pageTextContains('Test 1 was a failure.');

      // Test content plugin submission.
      $this->drupalGet("designs_test_form/{$parents}/{$array_parents}/{$source_id}");
      $this->submitForm(["{$parents}[design]" => 'library'], 'Change design');
      $label = $this->randomMachineName();
      $this->submitForm([
        "{$button}-content-addition-label" => $label,
        "{$button}-content-addition-machine" => 'test',
      ], "{$button}-content-addition-create");
      $this->submitForm([
        "{$parents}[content][test][plugin]" => 'test_content',
      ], "{$button}-content-test-submit");
      $this->submitForm([
        "{$parents}[content][test][config][test1]" => 'test',
        "{$parents}[content][test][config][test2]" => 'test',
      ], 'Submit');

      $outcome = $result;
      $outcome['content']['test'] = [
        'plugin' => 'test_content',
        'config' => [
          'test1' => 'test',
          'test2' => 'test-test',
          'label' => $label,
        ],
      ];
      $this->assertSession()->pageTextContains(json_encode($outcome));

      // Test remove content.
      $this->drupalGet("designs_test_form/{$parents}/{$array_parents}/{$source_id}");
      $this->submitForm(["{$parents}[design]" => 'library'], 'Change design');
      $label = $this->randomMachineName();
      $this->submitForm([
        "{$button}-content-addition-label" => $label,
        "{$button}-content-addition-machine" => 'test',
      ], "{$button}-content-addition-create");
      $this->submitForm([], "{$button}-content-test-remove");
      $this->submitForm([], 'Submit');
      $this->assertSession()->pageTextContains(json_encode($result));
    }

    // Process region form arrangement.
    if ($plugin->usesRegionsForm()) {
      // Check the addition aspect of the regions form.
      $this->drupalGet("designs_test_form/{$parents}/{$array_parents}/{$source_id}");
      $this->submitForm(["{$parents}[design]" => 'library'], 'Change design');
      $this->submitForm([
        "{$button}-regions-hamburger-addition-field" => 'text',
      ], "{$button}-regions-hamburger-addition-submit");
      $this->submitForm([
        "{$button}-regions-hamburger-addition-field" => 'content',
      ], "{$button}-regions-hamburger-addition-submit");
      $this->submitForm([], 'Submit');

      $outcome = $result;
      $outcome['regions']['hamburger'] = [
        'text',
        'content',
      ];
      $this->assertSession()->pageTextContains(json_encode($outcome));

      // Check the reordering aspect of the regions form.
      $this->drupalGet("designs_test_form/{$parents}/{$array_parents}/{$source_id}");
      $this->submitForm(["{$parents}[design]" => 'library'], 'Change design');
      $this->submitForm([
        "{$button}-regions-hamburger-addition-field" => 'text',
      ], "{$button}-regions-hamburger-addition-submit");
      $this->submitForm([
        "{$button}-regions-hamburger-addition-field" => 'content',
      ], "{$button}-regions-hamburger-addition-submit");
      $this->submitForm([
        "{$parents}[regions][hamburger][contents][1][weight]" => '-1',
      ], 'Submit');

      $outcome = $result;
      $outcome['regions']['hamburger'] = [
        'content',
        'text',
      ];
      $this->assertSession()->pageTextContains(json_encode($outcome));

      // Check the deletion aspect of the regions form.
      $this->drupalGet("designs_test_form/{$parents}/{$array_parents}/{$source_id}");
      $this->submitForm(["{$parents}[design]" => 'library'], 'Change design');
      $this->submitForm([
        "{$button}-regions-hamburger-addition-field" => 'text',
      ], "{$button}-regions-hamburger-addition-submit");
      $this->submitForm([
        "{$button}-regions-hamburger-addition-field" => 'content',
      ], "{$button}-regions-hamburger-addition-submit");
      $this->submitForm([], "{$button}-regions-hamburger-contents-0-remove");
      $this->submitForm([], 'Submit');

      $outcome = $result;
      $outcome['regions']['hamburger'] = [
        'content',
      ];
      $this->assertSession()->pageTextContains(json_encode($outcome));
    }

    // Test the combination of custom content and regions.
    if ($plugin->usesCustomContent() && $plugin->usesRegionsForm()) {
      // Check the addition aspect of the regions form.
      $this->drupalGet("designs_test_form/{$parents}/{$array_parents}/{$source_id}");
      $this->submitForm(["{$parents}[design]" => 'library'], 'Change design');
      $label = $this->randomMachineName();
      $this->submitForm([
        "{$button}-content-addition-label" => $label,
        "{$button}-content-addition-machine" => 'test',
      ], "{$button}-content-addition-create");
      $this->submitForm([
        "{$button}-regions-hamburger-addition-field" => 'test',
      ], "{$button}-regions-hamburger-addition-submit");
      $this->submitForm([], 'Submit');

      $outcome = $result;
      $outcome['content']['test'] = [
        'plugin' => 'text',
        'config' => [
          'value' => '',
          'label' => $label,
        ],
      ];
      $outcome['regions']['hamburger'] = [
        'test',
      ];
      $this->assertSession()->pageTextContains(json_encode($outcome));

      // Check the removal aspect of the regions form.
      $this->drupalGet("designs_test_form/{$parents}/{$array_parents}/{$source_id}");
      $this->submitForm(["{$parents}[design]" => 'library'], 'Change design');
      $label = $this->randomMachineName();
      $this->submitForm([
        "{$button}-content-addition-label" => $label,
        "{$button}-content-addition-machine" => 'test',
      ], "{$button}-content-addition-create");
      $this->submitForm([
        "{$button}-regions-hamburger-addition-field" => 'test',
      ], "{$button}-regions-hamburger-addition-submit");
      $this->submitForm([], "{$button}-content-test-remove");
      $this->submitForm([], 'Submit');

      $this->assertSession()->pageTextContains(json_encode($result));
    }
  }

  /**
   * Tests whether a source automatically provides default sources.
   */
  public function testSourceDefaults() {
    $result = $this->getBaseResult();
    $result['content'] = [];

    // Check no automatic default sources.
    $this->drupalGet("designs_test_form/a/a/designs_test_all");
    $this->submitForm(["a[design]" => 'library'], 'Change design');
    $this->submitForm([], 'Submit');

    $outcome = $result;
    $outcome['regions']['hamburger'] = [];
    $this->assertSession()->pageTextContains(json_encode($outcome));

    // Check the addition aspect of the regions form.
    $this->drupalGet("designs_test_form/a/a/designs_test_default");
    $this->submitForm(["a[design]" => 'library'], 'Change design');
    $this->submitForm([], 'Submit');

    $outcome = $result;
    $outcome['regions']['hamburger'] = [
      'text',
    ];
    $this->assertSession()->pageTextContains(json_encode($outcome));
  }

}
