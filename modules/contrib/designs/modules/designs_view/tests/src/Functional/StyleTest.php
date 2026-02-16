<?php

namespace Drupal\Tests\designs_view\Functional;

use Drupal\views\Views;

/**
 * Tests the style plugin.
 *
 * @group designs_view
 */
class StyleTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * Tests changing the style plugin and changing some options of a style.
   */
  public function testStyleUi() {
    $custom_id = strtolower($this->randomMachineName());
    $custom_label = $this->randomMachineName();
    $custom_text = $this->randomMachineName();
    $title_text = $this->randomMachineName();
    $attributes = "id=\"{$custom_id}\"";

    $view_name = 'test_view';
    $view_edit_url = "admin/structure/views/view/$view_name/edit";

    $style_plugin_url = "admin/structure/views/nojs/display/$view_name/default/style";
    $style_options_url = "admin/structure/views/nojs/display/$view_name/default/style_options";

    $this->drupalGet($style_plugin_url);
    $this->assertSession()->fieldValueEquals('style[type]', 'default');

    $edit = [
      'style[type]' => 'designs',
    ];
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->fieldExists('style_options[design][design]');

    $parents = "style_options[design]";
    $this->drupalDesign(
      $parents,
      ['attributes' => $attributes],
      [
        'id' => $custom_id,
        'label' => $custom_label,
        'text' => $custom_text,
      ],
      [
        'header',
        $custom_id,
        'rows',
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
    $this->drupalGet($style_options_url);

    $this->drupalGet($view_edit_url);
    $this->submitForm([], 'Save');
    $this->assertSession()->linkExists('Design', 0, 'Make sure the test style plugin is shown in the UI');

    $view = Views::getView($view_name);
    $view->initDisplay();
    $style = $view->display_handler->getOption('style');
    $this->assertEquals('designs', $style['type'], 'Make sure that the test_style got saved as used style plugin.');
    $design = $style['options']['design'];
    $this->assertEquals('content', $design['design']);
    $this->assertEquals($attributes, $design['settings']['attributes']['attributes']);
    $this->assertEquals('article', $design['settings']['tag']['value']);
    $this->assertEquals($custom_label, $design['content'][$custom_id]['config']['label']);
    $this->assertEquals($custom_text, $design['content'][$custom_id]['config']['value']);
    $this->assertEquals($title_text, $design['content']['title']['config']['value']);
    $this->assertTrue(in_array('header', $design['regions']['content']));
    $this->assertTrue(in_array('rows', $design['regions']['content']));
    $this->assertTrue(in_array($custom_id, $design['regions']['content']));
  }

}
