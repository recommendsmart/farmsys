<?php

namespace Drupal\designs;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\designs\Form\FormTrait;
use Drupal\designs\Form\SettingForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base for design settings.
 */
abstract class DesignSettingBase extends PluginBase implements DesignSettingInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;
  use DependencySerializationTrait;
  use FormTrait;

  /**
   * The design definition.
   *
   * @var array
   */
  protected $designDefinition;

  /**
   * The source plugin.
   *
   * @var \Drupal\designs\DesignSourceInterface
   */
  protected $sourcePlugin;

  /**
   * The design manager.
   *
   * @var \Drupal\designs\DesignManagerInterface
   */
  protected $manager;

  /**
   * The design content manager.
   *
   * @var \Drupal\designs\DesignContentManagerInterface
   */
  protected $contentManager;

  /**
   * The design setting manager.
   *
   * @var \Drupal\designs\DesignSettingManagerInterface
   */
  protected $settingManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The content for the setting.
   *
   * @var \Drupal\designs\DesignContentInterface|null
   */
  protected $content;

  /**
   * DesignSettingBase constructor.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin identifier.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\designs\DesignManagerInterface $manager
   *   The design manager.
   * @param \Drupal\designs\DesignContentManagerInterface $contentManager
   *   The design content manager.
   * @param \Drupal\designs\DesignSettingManagerInterface $settingManager
   *   The design setting manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    DesignManagerInterface $manager,
    DesignContentManagerInterface $contentManager,
    DesignSettingManagerInterface $settingManager,
    RendererInterface $renderer
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->manager = $manager;
    $this->contentManager = $contentManager;
    $this->settingManager = $settingManager;
    $this->renderer = $renderer;
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
      $container->get('plugin.manager.design_content'),
      $container->get('plugin.manager.design_setting'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $config = $this->configuration;
    if (empty($config['plugin'])) {
      unset($config['plugin']);
      unset($config['config']);
    }
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();

    $this->content = NULL;
    try {
      $this->content = $this->contentManager->createInstance(
        $this->configuration['plugin'],
        $this->configuration['config'] ?? [],
      );
    }
    catch (PluginNotFoundException $e) {
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDesignDefinition() {
    return $this->designDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function setDesignDefinition(array $definition) {
    $this->designDefinition = $definition;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'plugin' => '',
      'config' => [],
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
   */
  public function label() {
    if (isset($this->designDefinition['label'])) {
      return $this->designDefinition['label'];
    }
    return $this->pluginDefinition['label'];
  }

  /**
   * Get the description.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The description.
   */
  public function getDescription() {
    // The design description may specify a description but it is not yet
    // translated.
    if (isset($this->designDefinition['description'])) {
      return $this->designDefinition['description'];
    }
    // The plugin definition should be translated by default.
    if (isset($this->pluginDefinition['description'])) {
      return $this->pluginDefinition['description'];
    }
    // Only when absolutely no description is provided for the design setting.
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired() {
    if (isset($this->designDefinition['required'])) {
      return !empty($this->designDefinition['required']);
    }
    return !empty($this->pluginDefinition['required']);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitionValue($key, $default) {
    if (isset($this->designDefinition[$key])) {
      return $this->designDefinition[$key];
    }
    if (isset($this->pluginDefinition[$key])) {
      return $this->pluginDefinition[$key];
    }
    return $default;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array &$element) {
    // Use the content plugin behaviour.
    if ($this->content) {
      return $this->content->build($element);
    }
    // Use inbuilt version of the setting.
    return $this->buildSetting($element);
  }

  /**
   * Build the setting when not relying on content plugins.
   *
   * @param array $element
   *   The element array.
   *
   * @return array
   *   The render array.
   */
  abstract protected function buildSetting(array &$element);

  /**
   * {@inheritdoc}
   */
  public function process(array $build, array &$element) {
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form_handler = new SettingForm(
      $this->manager,
      $this->settingManager,
      $this->contentManager
    );

    return $form_handler
      ->setDesign($form['#design'])
      ->setSetting($this)
      ->buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form['#form_handler']->validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form['#form_handler']->submitForm($form, $form_state);
    $this->setConfiguration($form_state->getValue($form['#parents']));
    return $this->getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getUsedSources() {
    if ($this->content) {
      return $this->content->getUsedSources();
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getContent() {
    return $this->content;
  }

  /**
   * {@inheritdoc}
   */
  abstract public function buildForm(array $form, FormStateInterface $form_state);

  /**
   * {@inheritdoc}
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    $values = $values ?: [];

    // Restrict the values to the type and default configuration values.
    $result = [
      'type' => $values['type'] ?? $this->getPluginId(),
    ] + array_intersect_key($values, $this->defaultConfiguration());

    $form_state->setValue($form['#parents'], $result);
    return $result;
  }

}
