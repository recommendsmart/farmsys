<?php

namespace Drupal\Tests\designs\Unit;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\designs\DesignDefault;
use Drupal\designs\DesignDefinition;
use Drupal\designs\DesignManager;
use Drupal\designs\DesignSourceManagerInterface;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\designs\DesignManager
 * @group designs
 */
class DesignManagerTest extends UnitTestCase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme extension list.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeList;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Cache backend instance.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The design plugin manager.
   *
   * @var \Drupal\designs\DesignManagerInterface
   */
  protected $designPluginManager;

  /**
   * The design source manager.
   *
   * @var \Drupal\designs\DesignSourceManagerInterface
   */
  protected $designSourceManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpFilesystem();

    $root = vfsStream::url('root');

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);

    $this->moduleHandler->moduleExists('module_a')->willReturn(TRUE);
    $this->moduleHandler->moduleExists('theme_a')->willReturn(FALSE);
    $this->moduleHandler->moduleExists('theme_b')->willReturn(FALSE);
    $this->moduleHandler->moduleExists('core')->willReturn(FALSE);
    $this->moduleHandler->moduleExists('invalid_provider')->willReturn(FALSE);

    $module_a = new Extension($root, 'module', "modules/module_a/module_a.info.yml");
    $this->moduleHandler->getModule('module_a')->willReturn($module_a);
    $this->moduleHandler->getModuleDirectories()
      ->willReturn(['module_a' => "{$root}/modules/module_a"]);
    $this->moduleHandler->alter('design', Argument::type('array'))
      ->shouldBeCalled();

    $this->themeManager = $this->prophesize(ThemeManagerInterface::class);

    $this->themeList = $this->prophesize(ThemeExtensionList::class);

    $this->themeList->exists('theme_a')->willReturn(TRUE);
    $this->themeList->exists('theme_b')->willReturn(TRUE);
    $this->themeList->exists('core')->willReturn(FALSE);
    $this->themeList->exists('invalid_provider')->willReturn(FALSE);

    $list = [];
    foreach (['theme_a', 'theme_b', 'theme_c'] as $theme_name) {
      $theme = new Extension($root, 'theme', "themes/{$theme_name}/{$theme_name}.info.yml");
      $list[$theme_name] = $theme;
      $this->themeList->get($theme_name)->willReturn($theme);
      if ($theme_name === 'theme_b') {
        $this->themeManager->getActiveTheme()->willReturn($theme);
      }
    }
    $this->themeList->getList()->willReturn($list);
    $this->themeList->getBaseThemes($list, 'theme_b')->willReturn(['theme_a' => 'theme_a']);

    $this->cacheBackend = $this->prophesize(CacheBackendInterface::class);

    $this->designSourceManager = $this->prophesize(DesignSourceManagerInterface::class);

    $this->fileSystem = $this->prophesize(FileSystemInterface::class);
    $this->fileSystem->scanDirectory("{$root}/modules/module_a/designs", "/^.*\.yml$/")
      ->willReturn([
        "{$root}/modules/module_a/designs/test/test.designs.yml" => (object) [
          'uri' => "{$root}/modules/module_a/designs/test/test.designs.yml",
        ],
      ]);
    $this->fileSystem->scanDirectory("{$root}/themes/theme_a/designs", "/^.*\.yml$/")
      ->willReturn([]);
    $this->fileSystem->scanDirectory("{$root}/themes/theme_b/designs", "/^.*\.yml$/")
      ->willReturn([
        "{$root}/themes/theme_b/designs/construct/construct.designs.yml" => (object) [
          'uri' => "{$root}/themes/theme_b/designs/construct/construct.designs.yml",
        ],
      ]);

    $namespaces = new \ArrayObject(['Drupal\module_a' => "{$root}/modules/module_a/src"]);
    $this->designPluginManager = new DesignManager($root, $namespaces, $this->cacheBackend->reveal(), $this->moduleHandler->reveal(),  $this->themeList->reveal(), $this->themeManager->reveal(), $this->designSourceManager->reveal(), $this->fileSystem->reveal());
  }

  /**
   * @covers ::getDefinitions
   * @covers ::providerExists
   */
  public function testGetDefinitions() {
    $expected = [
      'module_a_single_design',
      'module_a_provided_design',
      'module_a_derived_design:provider',
      'module_a_overridden_design',
      'plugin_provided_design',
      'theme_a_overridden_design',
      'theme_a_provided_design',
    ];

    $design_definitions = $this->designPluginManager->getDefinitions();
    $this->assertEquals($expected, array_keys($design_definitions));
    $this->assertContainsOnlyInstancesOf(DesignDefinition::class, $design_definitions);
  }

  /**
   * @covers ::getDefinition
   * @covers ::processDefinition
   */
  public function testGetDefinition() {
    $design_definition = $this->designPluginManager->getDefinition('theme_a_provided_design');
    $this->assertSame('theme_a_provided_design', $design_definition->id());
    $this->assertSame('2 column design', (string) $design_definition->getLabel());
    $this->assertSame('Columns: 2', (string) $design_definition->getCategory());
    $this->assertSame('A theme provided design', (string) $design_definition->getDescription());
    $this->assertInstanceOf(TranslatableMarkup::class, $design_definition->getLabel());
    $this->assertInstanceOf(TranslatableMarkup::class, $design_definition->getCategory());
    $this->assertInstanceOf(TranslatableMarkup::class, $design_definition->getDescription());
    $this->assertSame('templates/twocol.html.twig', $design_definition->getTemplate());
    $this->assertSame( 'themes/theme_a', $design_definition->getPath());
    $this->assertSame(['module_a/twocol'], $design_definition->getLibraries());
    $expected_library_info = [
      'theme_a_provided_design' => [
        'dependencies' => [
          'module_a/twocol',
        ],
      ],
    ];
    $this->assertSame($expected_library_info, $design_definition->getLibraryInfo());
    $this->assertSame('theme_a_provided_design', $design_definition->getTemplateId());
    $this->assertSame('theme_a', $design_definition->getProvider());
    $this->assertSame('right', $design_definition->getDefaultRegion());
    $this->assertSame(DesignDefault::class, $design_definition->getClass());
    $expected_regions = [
      'left' => [
        'label' => new TranslatableMarkup('Left region', [], ['context' => 'design_region']),
      ],
      'right' => [
        'label' => new TranslatableMarkup('Right region', [], ['context' => 'design_region']),
      ],
    ];
    $regions = $design_definition->getRegions();
    $this->assertEquals($expected_regions, $regions);
    $this->assertInstanceOf(TranslatableMarkup::class, $regions['left']['label']);
    $this->assertInstanceOf(TranslatableMarkup::class, $regions['right']['label']);
    $expected_config_deps = [
      'module' => ['module_a'],
    ];
    $this->assertEquals($expected_config_deps, $design_definition->getConfigDependencies());

    $design_definition = $this->designPluginManager->getDefinition('module_a_provided_design');
    $this->assertSame('module_a_provided_design', $design_definition->id());
    $this->assertSame('1 column design', (string) $design_definition->getLabel());
    $this->assertSame('Columns: 1', (string) $design_definition->getCategory());
    $this->assertSame('A module provided design', (string) $design_definition->getDescription());
    $this->assertInstanceOf(TranslatableMarkup::class, $design_definition->getLabel());
    $this->assertInstanceOf(TranslatableMarkup::class, $design_definition->getCategory());
    $this->assertInstanceOf(TranslatableMarkup::class, $design_definition->getDescription());
    $this->assertSame('designs/onecol.html.twig', $design_definition->getTemplate());
    $this->assertSame('modules/module_a', $design_definition->getPath());
    $expected_libraries = [
      'theme_a/onecol',
      [
        'test' => [
          'css' => [
            'component' => [
              'css/onecol.css' => [],
              '/core/misc/print.css' => [],
            ],
          ],
          'js' => [
            '/core/misc/form.js' => [],
            'js/onecol.js' => [],
            'http://example.com/test.js' => ['type' => 'external'],
          ],
          'dependencies' => [
            'core/jquery',
          ],
        ],
      ],
    ];
    $this->assertSame($expected_libraries, $design_definition->getLibraries());
    $expected_library_info = [
      'module_a_provided_design' => [
        'dependencies' => [
          'theme_a/onecol',
          'designs/module_a_provided_design.test',
        ],
      ],
      'module_a_provided_design.test' => [
        'css' => [
          'component' => [
            '/modules/module_a/designs/css/onecol.css' => [],
            '/core/misc/print.css' => [],
          ],
        ],
        'js' => [
          '/core/misc/form.js' => [],
          '/modules/module_a/designs/js/onecol.js' => [],
          'http://example.com/test.js' => ['type' => 'external'],
        ],
        'dependencies' => [
          'core/jquery',
        ],
      ],
    ];
    $this->assertSame($expected_library_info, $design_definition->getLibraryInfo());
    $this->assertSame('module_a_provided_design', $design_definition->getTemplateId());
    $this->assertSame('designs/onecol.html.twig', $design_definition->getTemplate());
    $this->assertSame('modules/module_a', $design_definition->getPath());
    $this->assertSame('module_a', $design_definition->getProvider());
    $this->assertSame('top', $design_definition->getDefaultRegion());
    $this->assertSame(DesignDefault::class, $design_definition->getClass());
    $expected_regions = [
      'top' => [
        'label' => new TranslatableMarkup('Top region', [], ['context' => 'design_region']),
      ],
      'bottom' => [
        'label' => new TranslatableMarkup('Bottom region', [], ['context' => 'design_region']),
      ],
    ];
    $regions = $design_definition->getRegions();
    $this->assertEquals($expected_regions, $regions);
    $this->assertInstanceOf(TranslatableMarkup::class, $regions['top']['label']);
    $this->assertInstanceOf(TranslatableMarkup::class, $regions['bottom']['label']);
    $expected_config_deps = [
      'theme' => ['theme_a'],
    ];
    $this->assertEquals($expected_config_deps, $design_definition->getConfigDependencies());

    $design_definition = $this->designPluginManager->getDefinition('module_a_single_design');
    $this->assertSame('module_a_single_design', $design_definition->id());
    $expected_libraries = [
      'theme_a/onecol',
      [
        'test' => [
          'css' => [
            'component' => [
              'css/onecol.css' => [],
              '/core/misc/print.css' => [],
            ],
          ],
          'js' => [
            '/core/misc/form.js' => [],
            'js/onecol.js' => [],
            'http://example.com/test.js' => ['type' => 'external'],
          ],
          'dependencies' => [
            'core/jquery',
          ],
        ],
      ],
    ];
    $this->assertSame($expected_libraries, $design_definition->getLibraries());
    $expected_library_info = [
      'module_a_single_design' => [
        'dependencies' => [
          'theme_a/onecol',
          'designs/module_a_single_design.test',
        ],
      ],
      'module_a_single_design.test' => [
        'css' => [
          'component' => [
            '/modules/module_a/designs/test/css/onecol.css' => [],
            '/core/misc/print.css' => [],
          ],
        ],
        'js' => [
          '/core/misc/form.js' => [],
          '/modules/module_a/designs/test/js/onecol.js' => [],
          'http://example.com/test.js' => ['type' => 'external'],
        ],
        'dependencies' => [
          'core/jquery',
        ],
      ],
    ];
    $this->assertSame($expected_library_info, $design_definition->getLibraryInfo());

    $core_path = 'modules/module_a';
    $design_definition = $this->designPluginManager->getDefinition('plugin_provided_design');
    $this->assertSame('plugin_provided_design', $design_definition->id());
    $this->assertEquals('Design plugin', $design_definition->getLabel());
    $this->assertEquals('Columns: 1', $design_definition->getCategory());
    $this->assertEquals('Test design', $design_definition->getDescription());
    $this->assertInstanceOf(TranslatableMarkup::class, $design_definition->getLabel());
    $this->assertInstanceOf(TranslatableMarkup::class, $design_definition->getCategory());
    $this->assertInstanceOf(TranslatableMarkup::class, $design_definition->getDescription());
    $this->assertSame("$core_path/templates", $design_definition->getPath());
    $this->assertSame([], $design_definition->getLibraries());
    $this->assertSame('plugin_provided_design', $design_definition->getTemplateId());
    $this->assertSame("plugin-provided-design.html.twig", $design_definition->getTemplate());
    $this->assertSame('module_a', $design_definition->getProvider());
    $this->assertSame('main', $design_definition->getDefaultRegion());
    $this->assertSame('Drupal\module_a\Plugin\designs\design\TestDesign', $design_definition->getClass());
    $expected_regions = [
      'main' => [
        'label' => new TranslatableMarkup('Main Region', [], ['context' => 'design_region']),
      ],
    ];
    $regions = $design_definition->getRegions();
    $this->assertEquals($expected_regions, $regions);
    $this->assertInstanceOf(TranslatableMarkup::class, $regions['main']['label']);

    $design_definition = $this->designPluginManager->getDefinition('module_a_derived_design:provider');
    $this->assertSame('module_a_derived_design:provider', $design_definition->id());
    $this->assertSame("modules/module_a", $design_definition->getPath());
    $this->assertSame('module_a_derived_design_provider', $design_definition->getTemplateId());
    $this->assertSame('module_a', $design_definition->getProvider());
    $this->assertSame('Drupal\designs\DesignDefault', $design_definition->getClass());
  }

  /**
   * @covers ::processDefinition
   */
  public function testProcessDefinition() {
    $this->moduleHandler->alter('design', Argument::type('array'))
      ->shouldNotBeCalled();
    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage('The "module_a_derived_design:array_based" design definition must extend ' . DesignDefinition::class);
    $module_a_provided_design = <<<'EOS'
module_a_derived_design:
  deriver: \Drupal\Tests\designs\Unit\DesignDeriver
  array_based: true
EOS;
    vfsStream::create([
      'modules' => [
        'module_a' => [
          'module_a.designs.yml' => $module_a_provided_design,
        ],
      ],
    ]);
    $this->designPluginManager->getDefinitions();
  }

  /**
   * @covers ::getCategories
   */
  public function testGetCategories() {
    $expected = [
      'Columns: 1',
      'Columns: 2',
      'Columns: 3',
    ];
    $categories = $this->designPluginManager->getCategories();
    $this->assertEquals($expected, $categories);
  }

  /**
   * @covers ::getSortedDefinitions
   */
  public function testGetSortedDefinitions() {
    $expected = [
      'module_a_provided_design',
      'module_a_single_design',
      'plugin_provided_design',
      'module_a_overridden_design',
      'theme_a_provided_design',
      'theme_a_overridden_design',
      'module_a_derived_design:provider',
    ];

    $design_definitions = $this->designPluginManager->getSortedDefinitions();
    $this->assertEquals($expected, array_keys($design_definitions));
    $this->assertContainsOnlyInstancesOf(DesignDefinition::class, $design_definitions);
  }

  /**
   * @covers ::getGroupedDefinitions
   */
  public function testGetGroupedDefinitions() {
    $category_expected = [
      'Columns: 1' => [
        'module_a_provided_design',
        'module_a_single_design',
        'plugin_provided_design',
        'module_a_overridden_design',
      ],
      'Columns: 2' => [
        'theme_a_provided_design',
        'theme_a_overridden_design',
      ],
      'Columns: 3' => [
        'module_a_derived_design:provider',
      ],
    ];

    $definitions = $this->designPluginManager->getGroupedDefinitions();
    $this->assertEquals(array_keys($category_expected), array_keys($definitions));
    foreach ($category_expected as $category => $expected) {
      $this->assertArrayHasKey($category, $definitions);
      $this->assertEquals($expected, array_keys($definitions[$category]));
      $this->assertContainsOnlyInstancesOf(DesignDefinition::class, $definitions[$category]);
    }
  }

  /**
   * Sets up the filesystem with YAML files and annotated plugins.
   */
  protected function setUpFilesystem() {
    $module_a_provided_design = <<<'EOS'
module_a_provided_design:
  label: 1 column design
  category: 'Columns: 1'
  description: 'A module provided design'
  template: designs/onecol.html.twig
  libraries:
    - theme_a/onecol
    - test:
        css:
          component:
            css/onecol.css: {}
            /core/misc/print.css: {}
        js:
          /core/misc/form.js: {}
          js/onecol.js: {}
          http://example.com/test.js: { type: external }
        dependencies:
          - core/jquery
  regions:
    top:
      label: Top region
    bottom:
      label: Bottom region
module_a_derived_design:
  deriver: \Drupal\Tests\designs\Unit\DesignDeriver
  category: 'Columns: 3'
  invalid_provider: true
module_a_overridden_design:
  label: Overridden design
  category: 'Columns: 1'
  template: designs/onecol.html.twig
EOS;
    $module_a_single_design = <<<'EOS'
id: module_a_single_design
label: 1 column design delta
category: 'Columns: 1'
description: 'A module provided design'
template: test.html.twig
libraries:
  - theme_a/onecol
  - test:
      css:
        component:
          css/onecol.css: {}
          /core/misc/print.css: {}
      js:
        /core/misc/form.js: {}
        js/onecol.js: {}
        http://example.com/test.js: { type: external }
      dependencies:
        - core/jquery
EOS;
    $theme_a_provided_design = <<<'EOS'
theme_a_provided_design:
  class: '\Drupal\designs\DesignDefault'
  label: 2 column design
  category: 'Columns: 2'
  description: 'A theme provided design'
  template: templates/twocol.html.twig
  libraries:
    - module_a/twocol
  defaultRegion: right
  regions:
    left:
      label: Left region
    right:
      label: Right region
theme_a_overridden_design:
  class: '\Drupal\designs\DesignDefault'
  label: 2 column design
  category: 'Columns: 2'
  description: 'A theme provided design'
  template: templates/twocol.html.twig
  libraries:
    - module_a/twocol
  defaultRegion: right
  regions:
    left:
      label: Left region
    right:
      label: Right region
module_a_overridden_design:
  label: 'Overridden design'
  category: 'Columns: 1'
  template: designs/onecol.html.twig
EOS;
    $theme_b_provided_design = <<<'EOS'
id: theme_a_overridden_design
class: '\Drupal\designs\DesignDefault'
label: 2 column design overridden
category: 'Columns: 2'
description: 'A theme provided design'
template: templates/twocol.html.twig
libraries:
  - module_a/twocol
defaultRegion: left
regions:
  left:
    label: Left region
  right:
    label: Right region
EOS;
    $theme_c_provided_design = <<<'EOS'
theme_c_provided_design:
  class: '\Drupal\designs\DesignDefault'
  label: 2 column design C
  category: 'Columns: 2'
  description: 'A theme provided design'
  template: templates/twocol.html.twig
  libraries:
    - module_a/twocol
  defaultRegion: right
  regions:
    left:
      label: Left region
    right:
      label: Right region
EOS;
    $plugin_provided_design = <<<'EOS'
<?php
namespace Drupal\module_a\Plugin\designs\Design;
use Drupal\designs\DesignDefault;
/**
 * @Design(
 *   id = "plugin_provided_design",
 *   label = @Translation("Design plugin"),
 *   category = @Translation("Columns: 1"),
 *   description = @Translation("Test design"),
 *   path = "templates",
 *   template = "plugin-provided-design.html.twig",
 *   regions = {
 *     "main" = {
 *       "label" = @Translation("Main Region", context = "design_region")
 *     }
 *   }
 * )
 */
class TestDesign extends DesignDefault {}
EOS;
    vfsStream::setup('root');
    vfsStream::create([
      'modules' => [
        'module_a' => [
          'module_a.designs.yml' => $module_a_provided_design,
          'designs' => [
            'test' => [
              'test.designs.yml' => $module_a_single_design,
            ],
          ],
          'src' => [
            'Plugin' => [
              'designs' => [
                'design' => [
                  'TestDesign.php' => $plugin_provided_design,
                ],
              ],
            ],
          ],
        ],
      ],
    ]);
    vfsStream::create([
      'themes' => [
        'theme_a' => [
          'theme_a.designs.yml' => $theme_a_provided_design,
        ],
        'theme_b' => [
          'designs' => [
            'construct' => [
              'construct.designs.yml' => $theme_b_provided_design,
            ],
          ],
        ],
        'theme_c' => [
          'theme_c.designs.yml' => $theme_c_provided_design,
        ],
      ],
    ]);
  }

}


/**
 * Provides a dynamic design deriver for the test.
 */
class DesignDeriver extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if ($base_plugin_definition->get('array_based')) {
      $this->derivatives['array_based'] = [];
    }
    if ($base_plugin_definition->get('invalid_provider')) {
      $this->derivatives['invalid_provider'] = new DesignDefinition([
        'id' => 'invalid_provider',
        'provider' => 'invalid_provider',
      ]);
    }
    $this->derivatives['provider'] = new DesignDefinition([
      'id' => $base_plugin_definition->id() . ':provider',
      'template' => 'templates/threecol.html.twig',
    ] + $base_plugin_definition->getDefinition());
    return $this->derivatives;
  }

}
