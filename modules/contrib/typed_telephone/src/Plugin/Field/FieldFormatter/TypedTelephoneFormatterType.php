<?php

namespace Drupal\typed_telephone\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\typed_telephone\ConfigHelperService;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'typed_telephone_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "typed_telephone_default_formatter",
 *   label = @Translation("Typed telephone plain"),
 *   field_types = {
 *     "typed_telephone"
 *   }
 * )
 */
class TypedTelephoneFormatterType extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Our own ConfigHelperService instance to load and massage config data.
   *
   * @var \Drupal\typed_telephone\ConfigHelperService
   */
  protected $configHelperService;

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
      // Add any services to inject here.
      $container->get('typed_telephone.confighelper')
    );
  }

  /**
   * Construct a TypedTelephoneFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Defines an interface for entity field definitions.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\typed_telephone\ConfigHelperService $configHelper
   *   Custom helper service for loading and massaging config.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ConfigHelperService $configHelper) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->configHelper = $configHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'concatenated' => 1,
      'separator' => '-',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      'concatenated' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Concatenated'),
        '#description' => $this->t('Whether to concatenate type and number into a single string. Otherwise, two span tags will be generated.'),
        '#default_value' => $this->getSetting('concatenated'),
      ],
      'separator' => [
        '#type' => 'textfield',
        '#title' => $this->t('Type and telephone separator'),
        '#default_value' => $this->getSetting('separator'),
        '#description' => $this->t('The glue string to place between type and number.'),
      ],
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Concatenated: @value', ['@value' => (bool) $this->getSetting('concatenated') ? 'Yes' : 'No']);
    $summary[] = $this->t('Glue string: @value', ['@value' => $this->getSetting('separator')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewValue($item);
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    $type = $this->configHelper->getLabelFromShortname($item->get('teltype')->getValue());
    $number = $item->get('value')->getValue();

    return [
      '#theme' => 'typed_telephone_plain',
      '#type' => $type,
      '#number' => $number,
      '#concatenated' => $this->getSetting('concatenated'),
      '#glue' => $this->getSetting('separator'),
    ];
  }

}
