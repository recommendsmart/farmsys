<?php

namespace Drupal\Tests\designs_view\Functional;

use Drupal\block\Entity\Block;
use Drupal\views\Views;

/**
 * Tests the area plugins.
 *
 * @group designs_view
 */
class AreaTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * The block for testing the view.
   *
   * @var \Drupal\block\Entity\Block
   */
  protected $block;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->config('views.settings')
      ->set('display_extenders', ['design'])
      ->save();
    $this->block = Block::create([
      'id' => 'test_id',
      'plugin' => 'system_main_block',
    ]);
    $this->block->save();
  }

  /**
   * Provides test data for testAreas().
   */
  public function providerTestAreas() {
    return [
      ['header', 'header'],
      ['footer', 'footer'],
      ['empty', 'no results behavior'],
      ['pager', 'pager'],
    ];
  }

  /**
   * Test the UI for a view.
   *
   * @dataProvider providerTestAreas
   */
  public function testAreas($target, $button) {
    $options = $this->drupalAreaDesign($target, $button);
    $this->assertViewArea($options['view_name'], $target, $options);
  }

  /**
   * Builds the assertions based on the area.
   *
   * @param string $area
   *   The target area.
   * @param string $button
   *   The button text.
   *
   * @return array
   *   The randomized content.
   */
  protected function drupalAreaDesign($area, $button) {
    $block_id = $this->block->id();

    $options = [];
    $options['custom_id'] = strtolower($this->randomMachineName());
    $options['custom_label'] = $this->randomMachineName();
    $options['custom_text'] = $this->randomMachineName();
    $options['title_text'] = $this->randomMachineName();
    $options['attributes'] = "id=\"{$options['custom_id']}\"";

    /** @var \Drupal\views\Entity\View $view */
    $view = $this->randomView();
    $view_name = $view['id'];
    $options['view_name'] = $view['id'];

    // Automatically set the display extender option.
    $display_option_url = "admin/structure/views/nojs/display/{$view_name}/default/design";
    $this->drupalGet($display_option_url);
    $this->submitForm([], 'Apply');

    if ($area === 'pager') {
      $regions = [
        'first',
        $options['custom_id'],
        'last',
      ];
    }
    else {
      // Configure both the entity_test area header and the block header to
      // reference the given entities.
      $this->drupalGet("admin/structure/views/nojs/add-handler/{$view_name}/page_1/{$area}");
      $this->submitForm(['name[views.entity_block]' => TRUE], "Add and configure {$button}");
      $this->submitForm(['options[target]' => $block_id], 'Apply');

      $regions = [
        'entity_block',
        $options['custom_id'],
      ];
    }

    // Configure the design for the area.
    $this->drupalGet("admin/structure/views/nojs/design/{$view_name}/page_1/{$area}");

    $parents = "design";
    $this->drupalDesign(
      $parents,
      ['attributes' => $options['attributes']],
      [
        'id' => $options['custom_id'],
        'label' => $options['custom_label'],
        'text' => $options['custom_text'],
      ],
      $regions
    );
    $this->drupalSetupDesignContent($parents, [
      'title' => [
        'plugin' => 'text',
        'config' => [
          'value' => $options['title_text'],
        ],
      ],
    ]);
    $this->submitForm([], 'Apply');
    $this->submitForm([], 'Save');

    return $options;
  }

  /**
   * Performs the assertions of a view area.
   *
   * @param string $view_name
   *   The view identifier.
   * @param string $area
   *   The view area.
   * @param array $options
   *   The option values.
   */
  protected function assertViewArea($view_name, $area, array $options) {
    $attributes = $options['attributes'];
    $custom_id = $options['custom_id'];
    $custom_label = $options['custom_label'];
    $custom_text = $options['custom_text'];
    $title_text = $options['title_text'];

    $view = Views::getView($view_name);
    $view->initDisplay();
    $display = $view->displayHandlers->get('page_1');
    $options = $display->getOption('design');

    $design = $options[$area];
    $this->assertEquals('content', $design['design']);
    $this->assertEquals($attributes, $design['settings']['attributes']['attributes']);
    $this->assertEquals('article', $design['settings']['tag']['value']);
    $this->assertEquals($custom_label, $design['content'][$custom_id]['config']['label']);
    $this->assertEquals($custom_text, $design['content'][$custom_id]['config']['value']);
    $this->assertEquals($title_text, $design['content']['title']['config']['value']);
    if ($area === 'pager') {
      $this->assertTrue(in_array('first', $design['regions']['content']));
      $this->assertTrue(in_array('last', $design['regions']['content']));
    }
    else {
      $this->assertTrue(in_array('entity_block', $design['regions']['content']));
    }
    $this->assertTrue(in_array($custom_id, $design['regions']['content']));
  }

}
