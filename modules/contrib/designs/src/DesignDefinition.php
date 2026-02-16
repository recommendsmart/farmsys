<?php

namespace Drupal\designs;

use Drupal\Component\Plugin\Definition\DerivablePluginDefinitionInterface;
use Drupal\Component\Plugin\Definition\PluginDefinition;
use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Core\Plugin\Definition\DependentPluginDefinitionInterface;
use Drupal\Core\Plugin\Definition\DependentPluginDefinitionTrait;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides an implementation of a design definition and its metadata.
 */
class DesignDefinition extends PluginDefinition implements PluginDefinitionInterface, DerivablePluginDefinitionInterface, DependentPluginDefinitionInterface {

  use DependentPluginDefinitionTrait;

  /**
   * The name of the deriver of this design definition, if any.
   *
   * @var string|null
   */
  protected $deriver;

  /**
   * The human-readable name.
   *
   * @var string
   */
  protected $label;

  /**
   * An optional description for advanced designs.
   *
   * @var string
   */
  protected $description;

  /**
   * The human-readable category.
   *
   * @var string
   */
  protected $category;

  /**
   * An associative array of settings in this design.
   *
   * The key of the array is the machine name of the setting, and the value is
   * an associative array with the following keys:
   * - type: (string) The plugin identifier for the setting.
   * - label: (string) The human-readable name of the setting.
   * - default_value: (optional) The default value for the setting.
   * - preview: (optional) The preview value or values for the setting.
   *
   * Any remaining keys may have special meaning for the given setting plugin,
   * but are undefined here.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * An associative array of custom content in this design.
   *
   * The design custom content. The keys of the array are the machine names of
   * the content, and the values are an associative array with the following
   * keys:
   * - type: (string) The type for the custom content. One of 'default', 'twig',
   *   'token'.
   * - label: (string) The human-readable name of the custom content.
   * - value: (string) The value for the custom content.
   *
   * Any remaining keys may have special meaning for the given design plugin,
   * but are undefined here.
   *
   * @var array
   */
  protected $custom = [];

  /**
   * An associative array of regions in this design.
   *
   * The key of the array is the machine name of the region, and the value is
   * an associative array with the following keys:
   * - label: (string) The human-readable name of the region.
   * - preview: (optional) The preview value or values for the region.
   *
   * Any remaining keys may have special meaning for the given design plugin,
   * but are undefined here.
   *
   * @var array
   */
  protected $regions = [];

  /**
   * An associative array of libraries in this design.
   *
   * @var array
   */
  protected $libraries = [];

  /**
   * The template filename.
   *
   * @var string
   */
  protected $template;

  /**
   * Path (relative to the module or theme) to resources like icon or template.
   *
   * @var string
   */
  protected $path;

  /**
   * The default region.
   *
   * @var string
   */
  protected $defaultRegion;

  /**
   * Any additional properties and values.
   *
   * @var array
   */
  protected $additional = [];

  /**
   * DesignDefinition constructor.
   *
   * @param array $definition
   *   An array of values from the annotation.
   */
  public function __construct(array $definition) {
    foreach ($definition as $property => $value) {
      $this->set($property, $value);
    }
  }

  /**
   * Gets any arbitrary property.
   *
   * @param string $property
   *   The property to retrieve.
   *
   * @return mixed
   *   The value for that property, or NULL if the property does not exist.
   */
  public function get($property) {
    if (property_exists($this, $property)) {
      $value = $this->{$property} ?? NULL;
    }
    else {
      $value = $this->additional[$property] ?? NULL;
    }
    return $value;
  }

  /**
   * Sets a value to an arbitrary property.
   *
   * @param string $property
   *   The property to use for the value.
   * @param mixed $value
   *   The value to set.
   *
   * @return \Drupal\designs\DesignDefinition
   *   The object instance.
   */
  public function set($property, $value) {
    if (property_exists($this, $property)) {
      $this->{$property} = $value;
    }
    else {
      $this->additional[$property] = $value;
    }
    return $this;
  }

  /**
   * Gets the human-readable name of the design definition.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   The human-readable name of the design definition.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Sets the human-readable name of the design definition.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The human-readable name of the design definition.
   *
   * @return $this
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * Gets the description of the design definition.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   The description of the design definition.
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Sets the description of the design definition.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   The description of the design definition.
   *
   * @return $this
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * Gets the human-readable category of the design definition.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   The human-readable category of the design definition.
   */
  public function getCategory() {
    return $this->category;
  }

  /**
   * Sets the human-readable category of the design definition.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $category
   *   The human-readable category of the design definition.
   *
   * @return $this
   */
  public function setCategory($category) {
    $this->category = $category;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDeriver() {
    return $this->deriver;
  }

  /**
   * {@inheritdoc}
   */
  public function setDeriver($deriver) {
    $this->deriver = $deriver;
    return $this;
  }

  /**
   * Gets the settings for this design definition.
   *
   * @return array[]
   *   The design settings. The keys of the array are the machine names of the
   *   settings, and the values are an associative array with the following
   *   keys:
   *   - type: (string) The plugin identifier for the setting.
   *   - label: (string) The human-readable name of the setting.
   *   - default_value: (string) The default value for the setting.
   *   - preview: (string|string[]) The preview(s) for the setting.
   *   Any remaining keys may have special meaning for the given design plugin,
   *   but are undefined here.
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * Sets the settings for this design definition.
   *
   * @param array[] $settings
   *   An array of settings, see ::getSettings() for the format.
   *
   * @return $this
   */
  public function setSettings(array $settings) {
    $this->settings = $settings;
    return $this;
  }

  /**
   * Gets the machine-readable setting names.
   *
   * @return string[]
   *   An array of machine-readable setting names.
   */
  public function getSettingNames() {
    return array_keys($this->getSettings());
  }

  /**
   * Gets the human-readable setting labels.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   An array of human-readable setting labels.
   */
  public function getSettingLabels() {
    $settings = $this->getSettings();
    return array_combine(array_keys($settings), array_column($settings, 'label'));
  }

  /**
   * Gets the regions for this design definition.
   *
   * @return array[]
   *   The design regions. The keys of the array are the machine names of the
   *   regions, and the values are an associative array with the following keys:
   *   - label: (string) The human-readable name of the region.
   *   - preview: (string|string[]) The preview(s) of the region.
   *   Any remaining keys may have special meaning for the given design plugin,
   *   but are undefined here.
   */
  public function getRegions() {
    return $this->regions;
  }

  /**
   * Sets the regions for this design definition.
   *
   * @param array[] $regions
   *   An array of regions, see ::getRegions() for the format.
   *
   * @return $this
   */
  public function setRegions(array $regions) {
    $this->regions = $regions;
    return $this;
  }

  /**
   * Gets the machine-readable region names.
   *
   * @return string[]
   *   An array of machine-readable region names.
   */
  public function getRegionNames() {
    return array_keys($this->getRegions());
  }

  /**
   * Gets the human-readable region labels.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   An array of human-readable region labels.
   */
  public function getRegionLabels() {
    $regions = $this->getRegions();
    $labels = array_combine(array_keys($regions), array_column($regions, 'label'));
    return array_map(function ($label) {
      if ($label instanceof TranslatableMarkup) {
        return $label;
      }
      return new TranslatableMarkup($label);
    }, $labels);
  }

  /**
   * Gets the default region.
   *
   * Provides the first region if none has been specified.
   *
   * @return string
   *   The machine-readable name of the default region.
   */
  public function getDefaultRegion() {
    if ($this->defaultRegion) {
      return $this->defaultRegion;
    }
    return key($this->getRegions());
  }

  /**
   * Sets the default region.
   *
   * @param string $default_region
   *   The machine-readable name of the default region.
   *
   * @return $this
   */
  public function setDefaultRegion($default_region) {
    $this->defaultRegion = $default_region;
    return $this;
  }

  /**
   * Gets the libraries for this design definition.
   *
   * @return array[]|string[]
   *   The design libraries.
   */
  public function getLibraries() {
    return $this->libraries;
  }

  /**
   * Sets the libraries for this design definition.
   *
   * @param array[] $libraries
   *   An array of libraries, see ::getLibraries() for the format.
   *
   * @return $this
   */
  public function setLibraries(array $libraries) {
    $this->libraries = $libraries;
    return $this;
  }

  /**
   * Get the hook_library_info_build() definitions.
   *
   * @return array
   *   The library definitions.
   */
  public function getLibraryInfo() {
    $library_id = $this->getTemplateId();
    $base_path = $this->getPath();

    $dirname = dirname($this->getTemplate());
    if ($dirname !== '.') {
      $base_path .= '/' . $dirname;
    }

    $libraries = [$library_id => ['dependencies' => []]];
    foreach ($this->libraries as $library) {
      if (is_string($library)) {
        $libraries[$library_id]['dependencies'][] = $library;
        continue;
      }

      // Otherwise process each entry.
      foreach ($library as $key => $definition) {
        $dep = $library_id . '.' . $key;
        $libraries[$dep] = $this->processLibraryDefinition($definition, $base_path);
        $libraries[$library_id]['dependencies'][] = 'designs/' . $dep;
      }
    }
    return $libraries;
  }

  /**
   * Process each definition for relative paths.
   *
   * @param array $definition
   *   The css/js filename entries.
   * @param string $base_path
   *   The prefix base path.
   *
   * @return array
   *   The updated css/js filename entries.
   */
  protected function prefixLibraryEntry(array $definition, $base_path) {
    $results = [];
    foreach ($definition as $key => $value) {
      $is_external = isset($value['type']) && $value['type'] == 'external';
      $is_relative = substr($key, 0, 1) !== '/';
      if ($is_relative and !$is_external) {
        $results["/{$base_path}/{$key}"] = $value;
      }
      else {
        $results[$key] = $value;
      }
    }
    return $results;
  }

  /**
   * Prefixes relative css/js paths with the design path.
   *
   * @param array $definition
   *   The library definition.
   * @param string $base_path
   *   The base path.
   *
   * @return array
   *   The updated library definition.
   */
  protected function processLibraryDefinition(array $definition, $base_path) {
    if (!empty($definition['css'])) {
      foreach ($definition['css'] as $key => $components) {
        $definition['css'][$key] = $this->prefixLibraryEntry($components, $base_path);
      }
    }
    if (!empty($definition['js'])) {
      $definition['js'] = $this->prefixLibraryEntry($definition['js'], $base_path);
    }
    return $definition;
  }

  /**
   * Get the render array library attachments.
   *
   * @return string[]
   *   The libraries as attached to the render array.
   */
  public function getRenderAttached() {
    return ['designs/' . $this->getTemplateId()];
  }

  /**
   * Get the template identifier.
   *
   * @return string
   *   The template identifier as a machine name.
   */
  public function getTemplateId() {
    return str_replace(':', '_', $this->id);
  }

  /**
   * Get the template filename.
   *
   * @return string
   *   The template filename.
   */
  public function getTemplate() {
    return $this->template;
  }

  /**
   * Set the template filename.
   *
   * @param string $template
   *   The template filename.
   *
   * @return $this
   */
  public function setTemplate($template) {
    $this->template = $template;
    return $this;
  }

  /**
   * Get the full template path.
   *
   * @return string
   *   The template path.
   */
  public function getTemplatePath() {
    return $this->getPath() . '/' . $this->getTemplate();
  }

  /**
   * Gets the base path for this design definition.
   *
   * @return string
   *   The base path.
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * Sets the base path for this design definition.
   *
   * @param string $path
   *   The base path.
   *
   * @return $this
   *   The object instance.
   */
  public function setPath($path) {
    $this->path = $path;
    return $this;
  }

  /**
   * Get the definition.
   *
   * @return array
   *   The definition.
   */
  public function getDefinition() {
    $definition = [];
    foreach (array_keys(get_class_vars(static::class)) as $key) {
      if ($key !== 'additional' && property_exists($this, $key)) {
        $definition[$key] = $this->$key;
      }
    }
    $definition += $this->additional;
    return $definition;
  }

  /**
   * Get previews from the definition.
   *
   * @return array[]
   *   The preview configurations.
   */
  public function getPreviews() {
    // Get the settings iterations, always ensure there is at least one.
    $setting_iterations = $this->getPreviewSettings();
    if (!count($setting_iterations)) {
      $setting_iterations[] = [];
    }

    // Generate the region iterations for each setting combination.
    // Always ensure there is at least one iteration for display purposes.
    $region_iterations = $this->getPreviewRegions() ?: [[]];

    $setting_labels = $this->getSettingLabels();

    // Get all the preview options for regions.
    $previews = [];
    foreach ($setting_iterations as $index => $settings) {

      // Convert the setting to content value.
      $rows = [];
      foreach ($settings as $name => &$value) {
        $rows[] = [$setting_labels[$name], $value];
        $value = ['plugin' => 'text', 'config' => ['value' => $value]];
      }

      // Provide the context of settings for the preview.
      $previews[$index] = [
        '#type' => 'container',
        'settings' => [
          '#type' => 'details',
          '#title' => new TranslatableMarkup('Settings'),
          'table' => [
            '#type' => 'table',
            '#header' => [
              new TranslatableMarkup('Setting'),
              new TranslatableMarkup('Value'),
            ],
            '#rows' => $rows,
          ],
        ],
      ];

      // Generate the region combinations for the settings configuration.
      foreach ($region_iterations as $regions) {
        $previews[$index][] = [
          '#type' => 'design',
          '#design' => $this->id(),
          '#configuration' => [
            'settings' => $settings,
          ],
        ] + $regions;
      }
    }

    return $previews;
  }

  /**
   * Get the preview combinations for each of the settings.
   *
   * @return array
   *   The preview combinations for the settings.
   */
  protected function getPreviewSettings() {
    // Get all the preview options for settings.
    $settings = [];
    foreach ($this->settings as $id => $setting) {
      if (!empty($setting['preview'])) {
        $settings[$id] = (array) $setting['preview'];
      }
      elseif (isset($setting['default_value'])) {
        $settings[$id] = [$setting['default_value']];
      }
    }

    return $this->getPreviewIterations('', [], $settings);
  }

  /**
   * Get the preview values for each of the regions.
   *
   * @return array
   *   The preview combinations for the regions.
   */
  protected function getPreviewRegions() {
    // Design has specific region preview behaviour.
    if (isset($this->additional['preview'])) {
      $keys = array_keys($this->regions);
      $regions = [];
      foreach ((array) $this->additional['preview'] as $preview) {
        $result = [];
        foreach ($keys as $region) {
          if (!isset($preview[$region])) {
            continue;
          }
          foreach ((array) $preview[$region] as $value) {
            $result[$region][] = ['#markup' => Markup::create($value)];
          }
        }
        $regions[] = $result;
      }
      return $regions;
    }

    // Get all the preview options for regions.
    $regions = [];
    foreach ($this->regions as $id => $region) {
      if (!empty($region['preview'])) {
        foreach ((array) $region['preview'] as $preview) {
          $regions[$id][] = ['#markup' => Markup::create($preview)];
        }
      }
      else {
        $regions[$id] = [['#markup' => $region['label'] ?? $id]];
      }
    }

    return $this->getPreviewIterations('', [], $regions);
  }

  /**
   * Generate combination iterations for each of the preview values.
   *
   * @param string $index
   *   The current index.
   * @param array $current
   *   The current items.
   * @param array $right
   *   The items to the right of the current index.
   *
   * @return array
   *   The iterations as a flat array.
   */
  protected function getPreviewIterations(string $index, array $current, array $right) {
    // There are no more to the right, so the iterations are in current.
    $next_id = key($right);
    $next = array_shift($right);
    if (!$next) {
      $iterations = [];
      foreach ($current as $value) {
        $iterations[] = [$index => $value];
      }
      return $iterations;
    }

    // Start the preview iterations.
    if (!count($current)) {
      return $this->getPreviewIterations($next_id, $next, $right);
    }

    // Generate the iterations in combination with the next set of iterations.
    $iterations = [];
    foreach ($current as $value) {
      foreach ($this->getPreviewIterations($next_id, $next, $right) as $iteration) {
        $iterations[] = [$index => $value] + $iteration;
      }
    }
    return $iterations;
  }

}
