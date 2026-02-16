<?php

namespace Drupal\field_fallback\Plugin\FieldFallbackConverter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\field_fallback\Plugin\FieldFallbackConverterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Paragraphs Summary field fallback converter.
 *
 * This converter converts paragraphs to a summary using the
 * paragraphs_summary_token module.
 *
 * @FieldFallbackConverter(
 *   id = "paragraphs_summary",
 *   label = @Translation("Paragraphs summary"),
 *   source = {"entity_reference_revisions"},
 *   target = {"text_long"},
 *   weight = 0
 * )
 */
class ParagraphsSummaryFieldFallbackConverter extends FieldFallbackConverterBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The filter format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $filterFormatStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->moduleHandler = $module_handler;
    $this->filterFormatStorage = $entity_type_manager->getStorage('filter_format');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function convert(FieldItemListInterface $field) {
    $configuration = $this->getConfiguration();
    return [
      [
        // We don't trim the text here since that should be done in the field
        // formatter.
        'value' => \Drupal::service('paragraphs_summary_token.text_summary_builder')->build($field, NULL, $configuration['format']),
        'format' => $configuration['format'] ?? filter_default_format(),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return parent::defaultConfiguration() + [
      'trim_length' => 300,
      'format' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $configuration = $this->getConfiguration();

    $options = $this->getFormatOptions();

    $default_format = $configuration['format'];
    if ($default_format === NULL && count($options) === 1) {
      $default_format = current($options);
    }

    $form['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Text format'),
      '#options' => $options,
      '#default_value' => $default_format,
      '#access' => count($options) >= 1,
      '#weight' => 10,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * Get all filter formats as options.
   *
   * @return array
   *   The filter format options keyed by ID.
   */
  protected function getFormatOptions(): array {
    $options = [];
    $formats = $this->filterFormatStorage->loadByProperties(['status' => TRUE]);

    foreach ($formats as $format) {
      $options[$format->id()] = $format->label();
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(FieldDefinitionInterface $target_field, FieldDefinitionInterface $source_field): bool {
    return $source_field->getSetting('target_type') === 'paragraph' && $this->moduleHandler->moduleExists('paragraphs_summary_token');
  }

}
