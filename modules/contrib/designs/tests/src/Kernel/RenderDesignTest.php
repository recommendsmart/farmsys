<?php

namespace Drupal\Tests\designs\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * @coversDefaultClass \Drupal\designs\Element\RenderDesign
 * @group designs
 */
class RenderDesignTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'designs', 'designs_test'];

  /**
   * Asset the attribute exists.
   *
   * @param \SimpleXMLElement $element
   *   The element.
   * @param string $attribute
   *   The attribute.
   */
  public function assertAttribute(\SimpleXMLElement $element, $attribute) {
    $this->assertNotNull($element[$attribute]);
  }

  /**
   * Asset that no attribute exists.
   *
   * @param \SimpleXMLElement $element
   *   The element.
   * @param string $attribute
   *   The attribute.
   */
  public function assertNoAttribute(\SimpleXMLElement $element, $attribute) {
    $this->assertNull($element[$attribute]);
  }

  /**
   * Assert the attribute contains content.
   *
   * @param \SimpleXMLElement $element
   *   The element.
   * @param string $attribute
   *   The attribute name.
   * @param string $text
   *   The text to check.
   */
  public function assertAttributeText(\SimpleXMLElement $element, $attribute, $text) {
    $this->assertTrue(strpos($element[$attribute], $text) !== FALSE);
  }

  /**
   * Assert the attribute contains content.
   *
   * @param \SimpleXMLElement $element
   *   The element.
   * @param string $attribute
   *   The attribute name.
   * @param string $text
   *   The text to check.
   */
  public function assertAttributeNoText(\SimpleXMLElement $element, $attribute, $text) {
    $this->assertFalse(strpos($element[$attribute], $text) !== FALSE);
  }

  /**
   * Assert the element contains text.
   *
   * @param \SimpleXMLElement $element
   *   The element.
   * @param string $text
   *   The text.
   */
  public function assertElementText(\SimpleXMLElement $element, $text) {
    $this->assertTrue(strpos((string) $element, $text) !== FALSE);
  }

  /**
   * Assert the element does not contains text.
   *
   * @param \SimpleXMLElement $element
   *   The element.
   * @param string $text
   *   The text.
   */
  public function assertNoElementText(\SimpleXMLElement $element, $text) {
    $this->assertTrue(strpos((string) $element, $text) === FALSE);
  }

  /**
   * Test design under undefined conditions.
   */
  public function testSanity() {
    $template = [
      '#type' => 'design',
      '#design' => 'undefined',
      'content' => ['#markup' => 'show me the money'],
    ];
    $this->render($template);

    // Should still render content if design is undefined.
    $this->assertText('show me the money');
  }

  /**
   * Test a content design with no configuration.
   */
  public function testDesignWithoutConfiguration() {
    $template = [
      '#type' => 'design',
      '#design' => 'no_library',
      '#attributes' => ['data-peter' => 'czar'],
      'setting' => ['#markup' => 'frozen'],
      'extra' => ['#markup' => 'suburban recoil'],
      'hamburger' => ['#markup' => 'show me the money'],
    ];
    $this->render($template);

    $result = $this->xpath('//*[@id="no_library"]');
    $this->assertEquals(1, count($result));
    $this->assertAttributeText($result[0], 'data-setting', 'frozen');
    $this->assertAttributeText($result[0], 'data-peter', 'czar');
    $this->assertElementText($result[0], 'show me the money');
    $this->assertNoElementText($result[0], 'suburban recoil');
    $this->assertText('show me the money');
  }

  /**
   * Test a content design with configuration.
   */
  public function testMappedContentDesign() {
    $template = [
      '#type' => 'design',
      '#design' => 'no_library',
      '#configuration' => [
        'settings' => [
          'setting' => [
            'plugin' => 'text',
            'config' => [
              'value' => 'Crazy Train',
            ],
          ],
        ],
        'regions' => [
          'hamburger' => [
            'setting',
            'cancel',
          ],
          '_none' => [
            'cancel',
            'reduced',
          ],
        ],
      ],
      'setting' => ['#markup' => 'frozen'],
      'extra' => ['#markup' => 'suburban recoil'],
      'hamburger' => ['#markup' => 'show me the money'],
      'reduced' => ['#markup' => 'reduced price'],
      'cancel' => ['#markup' => 'cancelled products'],
    ];
    $this->render($template);

    $result = $this->xpath('//*[@id="no_library"]');
    $this->assertEquals(1, count($result));
    $this->assertAttributeText($result[0], 'data-setting', 'Crazy Train');
    $this->assertNoElementText($result[0], 'suburban recoil');
    $this->assertElementText($result[0], 'frozen');
    $this->assertElementText($result[0], 'cancelled products');
    $this->assertNoElementText($result[0], 'reduced price');
    $this->assertNoElementText($result[0], 'show me the money');
    $this->assertNoText('show me the money');
    $this->assertNoText('reduced price');
    $this->assertText('suburban recoil');
  }

  /**
   * Test the attributes behaviour..
   */
  public function testAttributes() {
    $template = [
      '#type' => 'design',
      '#design' => 'no_library',
      '#attributes' => ['data-attribute' => 'mayday'],
      '#configuration' => [
        'settings' => [
          'attributes' => [
            'plugin' => 'text',
            'config' => [
              'value' => 'data-prop="true" francine="true"',
            ],
          ],
        ],
      ],
    ];

    $this->render($template);

    $result = $this->xpath('//*[@id="no_library"]');
    $this->assertEquals(1, count($result));
    $this->assertAttributeText($result[0], 'data-prop', 'true');
  }

  /**
   * Test the settings conversion.
   */
  public function testSettings() {
    $template = [
      '#type' => 'design',
      '#design' => 'no_library',
      '#attributes' => ['data-attribute' => 'mayday'],
      '#configuration' => [
        'settings' => [
          'attributes' => [
            'attributes' => 'data-peter="none"',
            'existing' => TRUE,
          ],
          'setting' => [
            'plugin' => 'token',
            'config' => [
              'value' => 'user id [user:uid]',
            ],
          ],
        ],
      ],
      'hamburger' => ['#markup' => 'show me the money'],
      '#context' => ['user' => User::create(['uid' => 10])],
    ];
    $this->render($template);

    $result = $this->xpath('//*[@id="no_library"]');
    $this->assertEquals(1, count($result));
    $this->assertAttributeText($result[0], 'data-setting', 'user id 10');
    $this->assertAttributeText($result[0], 'data-attribute', 'mayday');
    $this->assertAttributeText($result[0], 'data-peter', 'none');

    $template = [
      '#type' => 'design',
      '#design' => 'no_library',
      '#attributes' => ['data-attribute' => 'mayday'],
      '#configuration' => [
        'settings' => [
          'attributes' => [
            'attributes' => 'data-peter="none"',
            'existing' => FALSE,
          ],
          'setting' => [
            'plugin' => 'twig',
            'config' => [
              'value' => 'user id {{ user.uid.value }}',
            ],
          ],
        ],
      ],
      'hamburger' => ['#markup' => 'show me the money'],
      '#context' => ['user' => User::create(['uid' => 10])],
    ];
    $this->render($template);

    $result = $this->xpath('//*[@id="no_library"]');
    $this->assertEquals(1, count($result));
    $this->assertAttributeText($result[0], 'data-setting', 'user id 10');
    $this->assertNoAttribute($result[0], 'data-attribute');
    $this->assertAttributeText($result[0], 'data-peter', 'none');

    $template = [
      '#type' => 'design',
      '#design' => 'no_library',
      '#attributes' => ['data-attribute' => 'mayday'],
      '#configuration' => [
        'settings' => [
          'setting' => [
            'plugin' => 'element',
            'config' => [
              'element' => 'hamburger',
            ],
          ],
        ],
      ],
      'hamburger' => ['#markup' => 'show me the money'],
      '#context' => ['user' => User::create(['uid' => 10])],
    ];
    $this->render($template);

    $result = $this->xpath('//*[@id="no_library"]');
    $this->assertEquals(1, count($result));
    $this->assertAttributeText($result[0], 'data-setting', 'show me the money');
    $this->assertElementText($result[0], 'show me the money');
  }

  /**
   * Test custom content.
   */
  public function testCustom() {
    $template = [
      '#type' => 'design',
      '#design' => 'no_library',
      '#configuration' => [
        'content' => [
          'isolate' => [
            'plugin' => 'text',
            'config' => [
              'value' => 'frozen goods',
            ],
          ],
        ],
        'regions' => [
          'hamburger' => ['isolate'],
        ],
      ],
      'hamburger' => ['#markup' => 'show me the money'],
      '#context' => ['user' => User::create(['uid' => 10])],
    ];
    $this->render($template);

    $result = $this->xpath('//*[@id="no_library"]');
    $this->assertEquals(1, count($result));
    $this->assertElementText($result[0], 'frozen goods');
    $this->assertNoElementText($result[0], 'show me the money');
    $this->assertNoText('show me the money');
  }

}
