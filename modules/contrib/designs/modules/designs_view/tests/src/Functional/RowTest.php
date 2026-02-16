<?php

namespace Drupal\Tests\designs_view\Functional;

use Drupal\views\Views;

/**
 * Tests the row plugin.
 *
 * @group designs_view
 */
class RowTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * Tests changing the row plugin and changing some options of a row.
   */
  public function testRowUi() {
    $custom_id = strtolower($this->randomMachineName());
    $custom_label = $this->randomMachineName();
    $custom_text = $this->randomMachineName();
    $title_text = $this->randomMachineName();
    $attributes = "id=\"{$custom_id}\"";

    $view_name = 'test_view';
    $view_edit_url = "admin/structure/views/view/$view_name/edit";

    $row_plugin_url = "admin/structure/views/nojs/display/$view_name/default/row";
    $row_options_url = "admin/structure/views/nojs/display/$view_name/default/row_options";

    $this->drupalGet($row_plugin_url);

    $edit = [
      'row[type]' => 'design',
    ];
    $this->submitForm($edit, 'Apply');

    $parents = "row_options[design]";
    $this->drupalDesign(
      $parents,
      ['attributes' => $attributes],
      [
        'id' => $custom_id,
        'label' => $custom_label,
        'text' => $custom_text,
      ],
      [
        'age',
        $custom_id,
        'name',
      ],
    );
    $this->drupalSetupDesignContent($parents, [
      'title' => [
        'plugin' => 'text',
        'config' => [
          'value' => $title_text,
        ],
      ],
    ]);
    $this->submitForm([], 'Apply');

    $this->drupalGet($view_edit_url);
    $this->submitForm([], 'Save');
    $this->assertSession()->linkExists('Design', 0, 'Make sure the test row plugin is shown in the UI');

    $view = Views::getView($view_name);
    $view->initDisplay();
    $row = $view->display_handler->getOption('row');

    $this->assertEquals('design', $row['type'], 'Make sure that the test_row got saved as used row plugin.');
    $design = $row['options']['design'];
    $this->assertEquals('content', $design['design']);
    $this->assertEquals($attributes, $design['settings']['attributes']['attributes']);
    $this->assertEquals('article', $design['settings']['tag']['value']);
    $this->assertEquals($custom_label, $design['content'][$custom_id]['config']['label']);
    $this->assertEquals($custom_text, $design['content'][$custom_id]['config']['value']);
    $this->assertEquals($title_text, $design['content']['title']['config']['value']);
    $this->assertTrue(in_array('age', $design['regions']['content']));
    $this->assertTrue(in_array('name', $design['regions']['content']));
    $this->assertTrue(in_array($custom_id, $design['regions']['content']));
  }

}
