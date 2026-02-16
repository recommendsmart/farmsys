<?php

namespace Drupal\designs;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\designs\Form\ConfigurationForm;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base for design plugins.
 */
class DesignDefault extends PluginBase implements DesignInterface, ContainerFactoryPluginInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The design definition.
   *
   * @var \Drupal\designs\DesignDefinition
   */
  protected $pluginDefinition;

  /**
   * The design manager.
   *
   * @var \Drupal\designs\DesignManagerInterface
   */
  protected $manager;

  /**
   * The design settings manager.
   *
   * @var \Drupal\designs\DesignSettingManagerInterface
   */
  protected $settingManager;

  /**
   * The content manager.
   *
   * @var \Drupal\designs\DesignContentManagerInterface
   */
  protected $contentManager;

  /**
   * The design source plugin.
   *
   * @var \Drupal\designs\DesignSourceInterface
   */
  protected $sourcePlugin;

  /**
   * The design settings.
   *
   * @var \Drupal\designs\DesignSettingInterface[]
   */
  protected $settings = [];

  /**
   * The design custom content.
   *
   * @var \Drupal\designs\DesignContentInterface[]
   */
  protected $content = [];

  /**
   * The design region.
   *
   * @var \Drupal\designs\DesignRegion[]
   */
  protected $regions = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    DesignManagerInterface $manager,
    DesignSettingManagerInterface $settingManager,
    DesignContentManagerInterface $contentManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->manager = $manager;
    $this->settingManager = $settingManager;
    $this->contentManager = $contentManager;
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.design'),
      $container->get('plugin.manager.design_setting'),
      $container->get('plugin.manager.design_content')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(array &$element) {
    $sources = $this->getUsedSources();
    $build = [
      '#type' => 'design',
      '#design' => $this->getPluginId(),
      '#configuration' => $this->getConfiguration(),
      '#context' => $this->getSourcePlugin()->getContexts($element),
    ];

    // Add the children from the element, and set the weight to 0. Weight is
    // determined by the configuration ordering of regions.
    foreach ($this->sourcePlugin->getElementSources($sources, $element) as $child => $item) {
      if (in_array($child, $sources)) {
        $build[$child] = ['#weight' => 0] + $item;
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);

    // The settings requires rebuilding.
    $this->settings = [];
    foreach ($this->pluginDefinition->getSettings() as $setting_id => $setting) {
      try {
        $this->settings[$setting_id] = $this->settingManager->createInstance($setting['type'], [])
          ->setDesignDefinition($setting)
          ->setConfiguration($this->configuration['settings'][$setting_id] ?? []);
      }
      catch (PluginNotFoundException $e) {
      }
    }

    // The custom content requires rebuilding.
    $this->content = [];
    foreach ($this->configuration['content'] as $content_id => $content) {
      try {
        $this->content[$content_id] = $this->contentManager->createInstance(
          $content['plugin'],
          $content['config'] ?? []
        );
      }
      catch (PluginNotFoundException $e) {
      }
    }

    // The regions require rebuilding.
    $this->regions = [];
    foreach ($this->pluginDefinition->getRegions() as $region_id => $region) {
      $sources = $this->configuration['regions'][$region_id] ?? [];
      $this->regions[$region_id] = new DesignRegion($region, $sources);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'design' => '',
      'settings' => [],
      'content' => [],
      'regions' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\designs\DesignDefinition
   *   The design definition.
   */
  public function getPluginDefinition() {
    return parent::getPluginDefinition();
  }

  /**
   * {@inheritdoc}
   */
  public function getSourcePlugin() {
    return $this->sourcePlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourcePlugin(DesignSourceInterface $source) {
    $this->sourcePlugin = $source;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting(string $setting_id): ?DesignSettingInterface {
    if (isset($this->settings[$setting_id])) {
      return $this->settings[$setting_id];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getContents() {
    return $this->content;
  }

  /**
   * {@inheritdoc}
   */
  public function getContent(string $content_id): ?DesignContentInterface {
    if (isset($this->content[$content_id])) {
      return $this->content[$content_id];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegions() {
    return $this->regions;
  }

  /**
   * {@inheritdoc}
   */
  public function getSources() {
    $names = $this->sourcePlugin->getSources();
    if (!empty($this->configuration['content'])) {
      foreach ($this->configuration['content'] as $key => $item) {
        $names[$key] = new TranslatableMarkup($item['config']['label'] ?? $key);
      }
      natsort($names);
    }
    return $names;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsedSources() {
    $plugin = $this->getPluginDefinition();

    // Get the custom content sources, these are used only when referenced by
    // settings or regions.
    $custom = [];
    foreach ($this->getContents() as $content_id => $content) {
      $custom[$content_id] = $content->getUsedSources();
    }

    // Cycle through each of the settings, and content to grab the used sources.
    // And then the content sources.
    $sources = [];

    // Processes the settings.
    foreach ($this->getSettings() as $setting) {
      $sources = array_merge($sources, $setting->getUsedSources());
    }

    // Process each of the regions.
    foreach ($plugin->getRegionNames() as $region) {
      $content = $this->configuration['regions'][$region] ?? [];
      $sources = array_merge($sources, $content);
    }

    // Add in the custom sources that were used in settings or regions.
    foreach (array_intersect($sources, array_keys($custom)) as $key) {
      $sources = array_merge($sources, $custom[$key]);
    }

    return array_unique($sources);
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultDesign() {
    $default_region = $this->getPluginDefinition()->getDefaultRegion();
    $default_sources = $this->getSourcePlugin()->getDefaultSources();

    $configuration = $this->getConfiguration();
    $configuration['regions'][$default_region] = $default_sources;
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config_form = new ConfigurationForm(
      $this->manager,
      $this->settingManager,
      $this->contentManager
    );
    return $config_form
      ->setDesign($this)
      ->buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\designs\Form\ConfigurationForm $config_form */
    $config_form = $form['#form_handler'];
    $config_form->validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\designs\Form\ConfigurationForm $config_form */
    $config_form = $form['#form_handler'];
    $result = $config_form->submitForm($form, $form_state);
    $this->setConfiguration($result);
  }

}
