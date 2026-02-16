<?php

namespace Drupal\Tests\designs_entity\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\designs\Traits\DesignsStandardTrait;
use Drupal\Tests\designs\Traits\DesignsTestTrait;

/**
 * Tests for the design display mode.
 *
 * @group designs_entity
 */
class DisplayTest extends BrowserTestBase {

  use DesignsTestTrait;
  use DesignsStandardTrait;

  /**
   * The theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * The modules.
   *
   * @var array
   */
  protected static $modules = [
    'designs',
    'designs_test',
    'designs_entity',
    'node',
    'field',
    'field_ui',
  ];

  /**
   * The random node type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeType;

  /**
   * The field information.
   *
   * @var array
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->nodeType = $this->drupalCreateContentType();
    $type_id = $this->nodeType->id();

    // Create a user that can edit and view the content.
    $web_user = $this->drupalCreateUser([
      "access content",
      "administer nodes",
      "administer node fields",
      "administer node form display",
      "administer node display",
      "create {$type_id} content",
      "edit any {$type_id} content",
    ]);
    $this->drupalLogin($web_user);

    // Create the fields associated with the node type.
    $field_storage = [
      'field_name' => 'field_test',
      'entity_type' => 'node',
      'type' => 'boolean',
    ];
    FieldStorageConfig::create($field_storage)->save();

    $this->field = [
      'entity_type' => 'node',
      'bundle' => $type_id,
      'field_name' => $field_storage['field_name'],
      'label' => $this->randomMachineName() . '_label',
      'description' => '[site:name]_description',
      'settings' => [],
    ];
    FieldConfig::create($this->field)->save();
  }

  /**
   * Setup an entity display (form/view).
   *
   * @param string $url
   *   The URL used for the entity display.
   * @param bool $form
   *   The display is form display.
   *
   * @return array
   *   The randomized content.
   */
  protected function drupalSetupDisplay($url, $form) {
    // Check simple submit generates the appropriate form output.
    $custom_id = strtolower($this->randomMachineName());
    $custom_label = $this->randomMachineName();
    $custom_text = $this->randomMachineName();
    $title_text = $this->randomMachineName();
    $attributes = "id=\"{$custom_id}\"";

    $this->drupalGet($url);
    $this->drupalDesign(
      "design",
      ['attributes' => $attributes],
      [
        'id' => $custom_id,
        'label' => $custom_label,
        'text' => $custom_text,
      ],
      []
    );
    $this->drupalSetupDesignContent("design", [
      'title' => [
        'plugin' => 'text',
        'config' => [
          'value' => $title_text,
        ],
      ],
    ]);

    // Add test field and custom field to the content region in the fields UI.
    $field_name = $this->field['field_name'];
    $this->getSession()->getPage()->selectFieldOption("fields[{$field_name}][region]", 'content');
    $this->assertTrue($this->assertSession()->optionExists("fields[{$field_name}][region]", 'content')->isSelected());

    $this->getSession()->getPage()->selectFieldOption("fields[{$custom_id}][region]", 'content');
    $this->assertTrue($this->assertSession()->optionExists("fields[{$custom_id}][region]", 'content')->isSelected());

    $this->submitForm([], 'Save');

    if ($form) {
      $display = \Drupal::service('entity_display.repository')
        ->getFormDisplay($this->field['entity_type'], $this->field['bundle']);
    }
    else {
      $display = \Drupal::service('entity_display.repository')
        ->getViewDisplay($this->field['entity_type'], $this->field['bundle']);
    }

    $design = $display->getThirdPartySettings('designs_entity');
    $this->assertIsArray($design);
    $this->assertEquals('content', $design['design']);
    $this->assertEquals($attributes, $design['settings']['attributes']['attributes']);
    $this->assertEquals('article', $design['settings']['tag']['value']);
    $this->assertEquals($custom_label, $design['content'][$custom_id]['config']['label']);
    $this->assertEquals($custom_text, $design['content'][$custom_id]['config']['value']);
    $this->assertEquals($title_text, $design['content']['title']['config']['value']);
    $this->assertTrue(in_array($field_name, $design['regions']['content']));
    $this->assertTrue(in_array($custom_id, $design['regions']['content']));

    // Get the randomized content used in the setup.
    return [
      'custom_id' => $custom_id,
      'custom_label' => $custom_label,
      'custom_text' => $custom_text,
      'title_text' => $title_text,
      'attributes' => $attributes,
    ];
  }

  /**
   * Setup an entity display with contextual information.
   *
   * @param string $url
   *   The url.
   *
   * @return array
   *   The randomized content.
   */
  protected function drupalSetupDesignContext($url) {
    // Check simple submit generates the appropriate form output.
    $custom_id = strtolower($this->randomMachineName());
    $custom_label = $this->randomMachineName();

    $this->drupalGet($url);
    $this->drupalDesignContext(
      "design",
      ['id' => $custom_id, 'label' => $custom_label],
      []
    );

    // Add test field and custom field to the content region in the fields UI.
    $this->getSession()->getPage()->selectFieldOption("fields[{$custom_id}][region]", 'content');
    $this->assertTrue($this->assertSession()->optionExists("fields[{$custom_id}][region]", 'content')->isSelected());

    $this->getSession()->getPage()->selectFieldOption("fields[{$custom_id}_1][region]", 'content');
    $this->assertTrue($this->assertSession()->optionExists("fields[{$custom_id}_1][region]", 'content')->isSelected());

    $this->submitForm([], 'Save');

    return [
      'custom_id' => $custom_id,
    ];
  }

  /**
   * Test the entity form mode behaviour.
   */
  public function testForm() {
    $type_id = $this->nodeType->id();

    $ids = $this->drupalSetupDisplay("admin/structure/types/manage/{$type_id}/form-display", TRUE);
    $custom_id = $ids['custom_id'];
    $custom_text = $ids['custom_text'];
    $title_text = $ids['title_text'];

    // Check that the form is located within an article tag.
    $this->drupalGet("node/add/{$type_id}");

    // Check the entity form is contained directly inside the form element.
    $xpath = $this->assertSession()->buildXPathQuery("//form/article[@id=\"{$custom_id}\"]");
    $this->assertEquals(1, count($this->xpath($xpath)));

    // Check that the custom text is also displayed on the page.
    $this->assertSession()->pageTextContains($custom_text);

    // Check that custom title did not override the default title.
    $this->assertSession()->pageTextNotContains($title_text);
  }

  /**
   * Test the entity view mode behaviour.
   */
  public function testView() {
    $type_id = $this->nodeType->id();

    $ids = $this->drupalSetupDisplay("admin/structure/types/manage/{$type_id}/display", FALSE);
    $custom_id = $ids['custom_id'];
    $custom_text = $ids['custom_text'];
    $title_text = $ids['title_text'];
    $field_name = $this->field['field_name'];

    // Create a node for display.
    $node = Node::create([
      'type' => $this->nodeType->id(),
      'title' => $this->randomString(),
      $field_name => TRUE,
    ]);
    $node->save();

    // Check that the form is located within an article tag.
    $this->drupalGet("node/{$node->id()}");

    // Check the entity display.
    $xpath = $this->assertSession()->buildXPathQuery("//article[@id=\"{$custom_id}\"]");
    $this->assertEquals(1, count($this->xpath($xpath)));

    // Check that the custom text is also displayed on the page.
    $this->assertSession()->pageTextContains($custom_text);

    // The title custom text should not be displayed.
    $this->assertSession()->pageTextNotContains($title_text);
  }

  /**
   * Test the form contextual information is passed.
   */
  public function testFormContext() {
    $type_id = $this->nodeType->id();
    $this->drupalSetupDesignContext("admin/structure/types/manage/{$type_id}/form-display");

    // Create node.
    $this->drupalGet("node/add/{$type_id}");

    // Check the settings work as expected when using undefined node.
    $xpath = $this->assertSession()->buildXPathQuery("//span[@id=\"node-\"]");
    $this->assertEquals(1, count($this->xpath($xpath)));

    $this->assertSession()->responseContains("node  token");
    $this->assertSession()->responseContains("node  twig");

    // Create a node for display.
    $node = Node::create([
      'type' => $this->nodeType->id(),
      'title' => $this->randomString(),
    ]);
    $node->save();

    $this->drupalGet("node/{$node->id()}/edit");

    $xpath = $this->assertSession()->buildXPathQuery("//div[@id=\"node-{$node->id()}\"]");
    $this->assertEquals(1, count($this->xpath($xpath)));

    $this->assertSession()->responseContains("node {$node->id()} token");
    $this->assertSession()->responseContains("node {$node->id()} twig");
  }

  /**
   * Test the form contextual information is passed.
   */
  public function testDisplayContext() {
    $type_id = $this->nodeType->id();
    $this->drupalSetupDesignContext("admin/structure/types/manage/{$type_id}/display");

    // Create a node for display.
    $node = Node::create([
      'type' => $this->nodeType->id(),
      'title' => $this->randomString(),
    ]);
    $node->save();

    $this->drupalGet("node/{$node->id()}");

    $xpath = $this->assertSession()->buildXPathQuery("//div[@id=\"node-{$node->id()}\"]");
    $this->assertEquals(1, count($this->xpath($xpath)));

    $this->assertSession()->responseContains("node {$node->id()} token");
    $this->assertSession()->responseContains("node {$node->id()} twig");
  }

}
