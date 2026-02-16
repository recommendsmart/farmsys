<?php

namespace Drupal\designs;

use Drupal\Component\Annotation\Plugin\Discovery\AnnotationBridgeDecorator;
use Drupal\Component\Assertion\Inspector;
use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryCachedTrait;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\UseCacheBackendTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscoveryDecorator;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\designs\Annotation\Design;
use Drupal\designs\Discovery\YamlDirectoryDiscoveryDecorator;

/**
 * Provides a plugin manager for designs.
 */
class DesignManager extends PluginManagerBase implements PluginManagerInterface, CachedDiscoveryInterface, CacheableDependencyInterface, DesignManagerInterface {

  use DiscoveryCachedTrait;
  use UseCacheBackendTrait;

  /**
   * The application root.
   *
   * @var string
   */
  protected $appRoot;

  /**
   * The cache key.
   *
   * @var string
   */
  protected $cacheKey;

  /**
   * An array of cache tags to use for the cached definitions.
   *
   * @var array
   */
  protected $cacheTags = [];

  /**
   * Name of the alter hook if one should be invoked.
   *
   * @var string
   */
  protected $alterHook;

  /**
   * The subdirectory within a namespace to look for plugins, or FALSE if the
   * plugins are in the top level of the namespace.
   *
   * @var string|bool
   */
  protected $subdir;

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The name of the annotation that contains the plugin definition.
   *
   * @var string
   */
  protected $pluginDefinitionAnnotationName;

  /**
   * The interface each plugin should implement.
   *
   * @var string|null
   */
  protected $pluginInterface;

  /**
   * An object that implements \Traversable which contains the root paths
   * keyed by the corresponding namespace to look for plugin implementations.
   *
   * @var \Traversable
   */
  protected $namespaces;

  /**
   * The active theme for the definitions.
   *
   * @var string
   */
  protected $activeTheme;

  /**
   * The theme list.
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
   * The source manager.
   *
   * @var \Drupal\designs\DesignSourceManagerInterface
   */
  protected $sourceManager;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $themeDiscovery;

  /**
   * The theme definitions.
   *
   * @var array
   */
  protected $themeDefinitions;

  /**
   * DesignPluginManager constructor.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Extension\ThemeExtensionList $theme_list
   *   The theme list.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager to select active theme.
   * @param \Drupal\designs\DesignSourceManagerInterface $sourceManager
   *   The design source manager.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system.
   */
  public function __construct(string $appRoot, \Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ThemeExtensionList $theme_list, ThemeManagerInterface $theme_manager, DesignSourceManagerInterface $sourceManager, FileSystemInterface $fileSystem) {
    $this->appRoot = $appRoot;
    $this->subdir = 'Plugin/designs/design';
    $this->namespaces = $namespaces;
    $this->pluginDefinitionAnnotationName = Design::class;
    $this->pluginInterface = DesignInterface::class;
    $this->moduleHandler = $module_handler;

    $this->themeList = $theme_list;
    $this->themeManager = $theme_manager;
    $this->sourceManager = $sourceManager;
    $this->fileSystem = $fileSystem;

    $this->alterHook = 'design';
    $this->setCacheBackend($cache_backend, 'design');
  }


  /**
   * Initialize the cache backend.
   *
   * Plugin definitions are cached using the provided cache backend.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param string $cache_key
   *   Cache key prefix to use.
   * @param array $cache_tags
   *   (optional) When providing a list of cache tags, the cached plugin
   *   definitions are tagged with the provided cache tags. These cache tags can
   *   then be used to clear the corresponding cached plugin definitions. Note
   *   that this should be used with care! For clearing all cached plugin
   *   definitions of a plugin manager, call that plugin manager's
   *   clearCachedDefinitions() method. Only use cache tags when cached plugin
   *   definitions should be cleared along with other, related cache entries.
   */
  public function setCacheBackend(CacheBackendInterface $cache_backend, $cache_key, array $cache_tags = []) {
    assert(Inspector::assertAllStrings($cache_tags), 'Cache Tags must be strings.');
    $this->cacheBackend = $cache_backend;
    $this->cacheKey = $cache_key;
    $this->cacheTags = $cache_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $activeTheme = $this->themeManager->getActiveTheme()->getName();
    $definitions = $this->getCachedDefinitions($activeTheme);
    if (!isset($definitions)) {
      $definitions = $this->findDefinitions($activeTheme);
      $this->setCachedDefinitions($definitions, $activeTheme);
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    if ($this->cacheBackend) {
      if ($this->cacheTags) {
        // Use the cache tags to clear the cache.
        Cache::invalidateTags($this->cacheTags);
      }
      else {
        $this->cacheBackend->delete($this->cacheKey);
      }
    }
    $this->definitions = NULL;
  }

  /**
   * Get the cache key for the active theme.
   *
   * @param string $activeTheme
   *   The active theme.
   *
   * @return string
   *   The cache key.
   */
  protected function getCacheKey(string $activeTheme) {
    return "{$this->cacheKey}:{$activeTheme}";
  }

  /**
   * Returns the cached plugin definitions of the decorated discovery class.
   *
   * @param string $activeTheme
   *   The active theme for the definitions.
   *
   * @return array|null
   *   On success this will return an array of plugin definitions. On failure
   *   this should return NULL, indicating to other methods that this has not
   *   yet been defined. Success with no values should return as an empty array
   *   and would actually be returned by the getDefinitions() method.
   */
  protected function getCachedDefinitions(string $activeTheme) {
    $cacheKey = $this->getCacheKey($activeTheme);
    if (!isset($this->definitions) && $cache = $this->cacheGet($cacheKey)) {
      $this->definitions = $cache->data;
    }
    return $this->definitions;
  }

  /**
   * Sets a cache of plugin definitions for the decorated discovery class.
   *
   * @param array $definitions
   *   List of definitions to store in cache.
   * @param string $activeTheme
   *   The active theme for the definitions.
   */
  protected function setCachedDefinitions($definitions, string $activeTheme) {
    $cacheKey = $this->getCacheKey($activeTheme);
    $this->cacheSet($cacheKey, $definitions, Cache::PERMANENT, $this->cacheTags);
    $this->definitions = $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function useCaches($use_caches = FALSE) {
    $this->useCaches = $use_caches;
    if (!$use_caches) {
      $this->definitions = NULL;
    }
  }

  /**
   * Performs extra processing on plugin definitions.
   */
  public function processDefinition(&$definition, $plugin_id) {
    if (!$definition instanceof DesignDefinition) {
      throw new InvalidPluginDefinitionException($plugin_id, sprintf('The "%s" design definition must extend %s', $plugin_id, DesignDefinition::class));
    }
    // Keep class definitions standard with no leading slash.
    $definition->setClass(ltrim($definition->getClass(), '\\'));

    // Add the module or theme path to the 'path'.
    $provider = $definition->getProvider();
    if ($this->moduleHandler->moduleExists($provider)) {
      $base_path = $this->moduleHandler->getModule($provider)->getPath();
    }
    elseif ($this->themeList->exists($provider)) {
      $base_path = $this->themeList->get($provider)->getPath();
    }
    else {
      $base_path = '';
    }

    $path = $definition->getPath();
    $path = !empty($path) ? $base_path . '/' . $path : $base_path;
    $definition->setPath($path);

    // Add dependencies from libraries.
    foreach ($definition->getLibraries() as $library) {
      if (!is_string($library)) {
        continue;
      }
      $config_dependencies = $definition->getConfigDependencies();
      [$library_provider] = explode('/', $library, 2);
      if ($this->moduleHandler->moduleExists($library_provider)) {
        $config_dependencies['module'][] = $library_provider;
      }
      elseif ($this->themeList->exists($library_provider)) {
        $config_dependencies['theme'][] = $library_provider;
      }
      $definition->setConfigDependencies($config_dependencies);
    }

    if (!$definition->getDefaultRegion()) {
      $definition->setDefaultRegion(key($definition->getRegions()));
    }

    // Make sure settings are translatable.
    $settings = $this->getTranslatedDefinitions(
      $definition->getSettings(), ['label', 'description']
    );
    $definition->setSettings($settings);

    // Makes sure region names are translatable.
    $regions = $this->getTranslatedDefinitions(
      $definition->getRegions(), ['label', 'description']
    );
    $definition->setRegions($regions);
  }

  /**
   * {@inheritdoc}
   */
  protected function getFactory() {
    if (!$this->factory) {
      $this->factory = new ContainerFactory($this, $this->pluginInterface);
    }
    return $this->factory;
  }

  /**
   * Finds plugin definitions.
   *
   * @param string $activeTheme
   *   The active theme.
   *
   * @return array
   *   List of definitions to store in cache.
   */
  protected function findDefinitions(string $activeTheme) {
    $discovery = $this->getDiscoveryByDirectories($this->moduleHandler->getModuleDirectories());
    $definitions = $discovery->getDefinitions();

    // Add definitions for active theme.
    if ($this->themeList->exists($activeTheme)) {
      $discovery = $this->getDiscoveryByDirectories([
        $activeTheme => $this->appRoot . '/' . $this->themeList->get($activeTheme)
            ->getPath(),
      ]);
      $definitions += $discovery->getDefinitions();

      // Add definitions from most base theme to current active theme.
      $themes = $this->themeList->getBaseThemes($this->themeList->getList(), $activeTheme);

      // Process themes for definitions.
      foreach (array_keys($themes) as $theme) {
        $discovery = $this->getDiscoveryByDirectories([
          $theme => $this->appRoot . '/' . $this->themeList->get($theme)
              ->getPath(),
        ]);
        $definitions += $discovery->getDefinitions();
      }
    }

    foreach ($definitions as $plugin_id => &$definition) {
      $this->processDefinition($definition, $plugin_id);
    }
    $this->alterDefinitions($definitions);
    // If this plugin was provided by a module that does not exist, remove the
    // plugin definition.
    foreach ($definitions as $plugin_id => $plugin_definition) {
      $provider = $this->extractProviderFromDefinition($plugin_definition);
      if ($provider && !in_array($provider, [
          'core',
          'component',
        ]) && !$this->providerExists($provider)) {
        unset($definitions[$plugin_id]);
      }
    }
    return $definitions;
  }

  /**
   * Extracts the provider from a plugin definition.
   *
   * @param mixed $plugin_definition
   *   The plugin definition. Usually either an array or an instance of
   *   \Drupal\Component\Plugin\Definition\PluginDefinitionInterface
   *
   * @return string|null
   *   The provider string, if it exists. NULL otherwise.
   */
  protected function extractProviderFromDefinition($plugin_definition) {
    if ($plugin_definition instanceof PluginDefinitionInterface) {
      return $plugin_definition->getProvider();
    }

    // Attempt to convert the plugin definition to an array.
    if (is_object($plugin_definition)) {
      $plugin_definition = (array) $plugin_definition;
    }

    if (isset($plugin_definition['provider'])) {
      return $plugin_definition['provider'];
    }
  }

  /**
   * Invokes the hook to alter the definitions if the alter hook is set.
   *
   * @param $definitions
   *   The discovered plugin definitions.
   */
  protected function alterDefinitions(&$definitions) {
    if ($this->alterHook) {
      $this->moduleHandler->alter($this->alterHook, $definitions);
    }
  }

  /**
   * Determines if the provider of a definition exists.
   *
   * @return bool
   *   TRUE if provider exists, FALSE otherwise.
   */
  protected function providerExists($provider) {
    return $this->moduleHandler->moduleExists($provider) || $this->themeList->exists($provider);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->cacheTags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * Get all the directories with designs located within them.
   *
   * @param array $directories
   *   The extension directories.
   *
   * @return array
   */
  protected function getDesignDirectories(array $directories) {
    return array_map(function ($directory) {
      if (file_exists("{$directory}/designs")) {
        $designs = $this->fileSystem->scanDirectory("{$directory}/designs", "/^.*\.yml$/");
        $designs = array_map(function ($file) {
          return dirname($file->uri);
        }, $designs);
        return array_unique(array_values($designs));
      }
      return [];
    }, $directories);
  }

  /**
   * Get discovery as applicable to directories.
   *
   * @param array $directories
   *   The list of directories.
   *
   * @return \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   *   The discovery.
   */
  protected function getDiscoveryByDirectories(array $directories) {
    $discovery = new AnnotatedClassDiscovery($this->subdir, $this->namespaces, $this->pluginDefinitionAnnotationName, []);
    $discovery = new YamlDiscoveryDecorator($discovery, 'designs', $directories);
    $discovery
      ->addTranslatableProperty('label')
      ->addTranslatableProperty('description')
      ->addTranslatableProperty('category');

    // Scan for designs within designs directory.
    $designs = $this->getDesignDirectories($directories);

    $discovery = new YamlDirectoryDiscoveryDecorator($discovery, 'designs', $designs, $directories);
    $discovery
      ->addTranslatableProperty('label')
      ->addTranslatableProperty('description')
      ->addTranslatableProperty('category');
    $discovery = new AnnotationBridgeDecorator($discovery, $this->pluginDefinitionAnnotationName);
    return new ContainerDerivativeDiscoveryDecorator($discovery);
  }

  /**
   * Get the labels as translatable markup.
   *
   * @param array[] $array
   *   The array of arrays with a key of 'label'.
   * @param array $keys
   *   The keys.
   *
   * @return array
   *   The array having been translated.
   */
  protected function getTranslatedDefinitions(array $array, array $keys) {
    return array_map(function ($item) use ($keys) {
      foreach ($keys as $key) {
        if (!isset($item[$key])) {
          continue;
        }
        if (!$item[$key] instanceof TranslatableMarkup) {
          $item[$key] = new TranslatableMarkup($item[$key], [], ['context' => 'design_region']);
        }
      }
      return $item;
    }, $array);
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraryImplementations() {
    $libraries = [];
    /** @var \Drupal\designs\DesignDefinition[] $definitions */
    $definitions = $this->getDefinitions();
    foreach ($definitions as $definition) {
      $libraries += $definition->getLibraryInfo();
    }
    return $libraries;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories() {
    // Fetch all categories from definitions and remove duplicates.
    $categories = array_unique(array_values(array_map(function (DesignDefinition $definition) {
      return $definition->getCategory();
    }, $this->getDefinitions())));
    natcasesort($categories);
    return array_values($categories);
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\designs\DesignDefinition[]
   *   The design definitions.
   */
  public function getSortedDefinitions(array $definitions = NULL, $label_key = 'label') {
    // Sort the plugins first by category, then by label.
    $definitions = $definitions ?? $this->getDefinitions();
    uasort($definitions, function (DesignDefinition $a, DesignDefinition $b) {
      if ($a->getCategory() != $b->getCategory()) {
        return strnatcasecmp($a->getCategory(), $b->getCategory());
      }
      return strnatcasecmp($a->getLabel(), $b->getLabel());
    });
    return $definitions;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\designs\DesignDefinition[][]
   *   The grouped design definitions.
   */
  public function getGroupedDefinitions(array $definitions = NULL, $label_key = 'label') {
    $definitions = $this->getSortedDefinitions($definitions ?? $this->getDefinitions(), $label_key);
    $grouped_definitions = [];
    foreach ($definitions as $id => $definition) {
      $grouped_definitions[(string) $definition->getCategory()][$id] = $definition;
    }
    return $grouped_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getDesignOptions() {
    $design_options = [];
    foreach ($this->getGroupedDefinitions() as $category => $design_definitions) {
      foreach ($design_definitions as $name => $design_definition) {
        $design_options[$category][$name] = $design_definition->getLabel();
      }
    }
    return $design_options;
  }

  /**
   * {@inheritdoc}
   */
  public function createSourcedInstance($design_id, array $design_configuration, $source_id, array $source_configuration) {
    try {
      $design = $this->createInstance($design_id, $design_configuration);
      $source = $this->sourceManager->createInstance($source_id, $source_configuration);
      $design->setSourcePlugin($source);
    } catch (PluginNotFoundException $e) {
      return NULL;
    }
    return $design;
  }

}
