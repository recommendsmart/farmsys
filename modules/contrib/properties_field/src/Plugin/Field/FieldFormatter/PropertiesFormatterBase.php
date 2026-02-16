<?php

namespace Drupal\properties_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\properties_field\PropertiesValueType\PropertiesValueTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for properties formatters.
 */
abstract class PropertiesFormatterBase extends FormatterBase {

  /**
   * Plugin manager for the properties value types.
   *
   * @var \Drupal\properties_field\PropertiesValueType\PropertiesValueTypeManager
   */
  protected $valueTypeManager;

  /**
   * The properties value type plugins.
   *
   * @var \Drupal\properties_field\PropertiesValueType\PropertiesValueTypeInterface[]
   */
  protected $valueTypePlugins = [];

  /**
   * Class constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\properties_field\PropertiesValueType\PropertiesValueTypeManager $properties_value_type_manager
   *   Plugin manager for the properties value types.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, PropertiesValueTypeManager $properties_value_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->valueTypeManager = $properties_value_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.properties_value_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'value_types' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['value_types'] = [];

    foreach ($this->getValueTypePlugins() as $plugin_id => $plugin) {
      $element_in = [
        '#type' => 'details',
        '#title' => $plugin->getPluginDefinition()['label'],
        '#open' => FALSE,
      ];

      $element = $plugin->formatterSettingsForm($element_in, $form_state, $form);

      if ($element !== $element_in) {
        $form['value_types'][$plugin_id] = $element;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $configurations = $this->getSetting('value_types');

    foreach ($configurations as $plugin_id => $configuration) {
      if (!$configuration) {
        continue;
      }

      if (!$plugin = $this->getValueTypePlugin($plugin_id)) {
        continue;
      }

      foreach ($plugin->formatterSettingsSummary() as $plugin_summary) {
        $summary[] = $this->t('<strong>@plugin:</strong> @summary', [
          '@plugin' => $plugin->getPluginDefinition()['label'],
          '@summary' => $plugin_summary,
        ]);
      }
    }

    return $summary;
  }

  /**
   * Get all properties value type plugins.
   *
   * @return \Drupal\properties_field\PropertiesValueType\PropertiesValueTypeInterface[]
   *   All properties value type plugins keyed by plugin ID.
   */
  protected function getValueTypePlugins() {
    $plugins = [];
    foreach ($this->valueTypeManager->getDefinitions() as $plugin_id => $definition) {
      $plugins[$plugin_id] = $this->getValueTypePlugin($plugin_id);
    }

    return $plugins;
  }

  /**
   * Get the properties value type plugin.
   *
   * @param string $plugin_id
   *   The properties value type plugin ID.
   *
   * @return \Drupal\properties_field\PropertiesValueType\PropertiesValueTypeInterface|null
   *   The properties value type plugin or NULL if it doesnt exist.
   */
  protected function getValueTypePlugin($plugin_id) {
    if (isset($this->valueTypePlugins[$plugin_id])) {
      return $this->valueTypePlugins[$plugin_id];
    }

    if (!$this->valueTypeManager->hasDefinition($plugin_id)) {
      return NULL;
    }

    $configuration = $this->getSetting('value_types');
    $configuration = $configuration[$plugin_id] ?? [];

    /** @var \Drupal\properties_field\PropertiesValueType\PropertiesValueTypeInterface $plugin */
    $plugin = $this->valueTypeManager->createInstance($plugin_id, $configuration);
    $this->valueTypePlugins[$plugin_id] = $plugin;

    return $this->valueTypePlugins[$plugin_id];
  }

}
