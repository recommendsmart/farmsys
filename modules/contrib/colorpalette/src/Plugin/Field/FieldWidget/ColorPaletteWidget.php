<?php

namespace Drupal\colorpalette\Plugin\Field\FieldWidget;

use Drupal\colorpalette\ColorPaletteUtility;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'colorpalette' widget.
 *
 * @FieldWidget(
 *   id = "colorpalette",
 *   label = @Translation("Color Palette"),
 *   field_types = {
 *     "entity_reference",
 *     "string",
 *     "text"
 *   }
 * )
 */
class ColorPaletteWidget extends WidgetBase {

  /**
   * The color palette utility.
   *
   * @var \Drupal\colorpalette\ColorPaletteUtility
   */
  protected $colorPalette;

  /**
   * The link generator service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'filter_tags' => '',
    ] + parent::defaultSettings();
  }

  /**
   * Constructs a WidgetBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator service to create anchor links.
   * @param \Drupal\colorpalette\ColorPaletteUtility $colorpalette_utility
   *   The color palette utility.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, LinkGeneratorInterface $link_generator, ColorPaletteUtility $colorpalette_utility) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->fieldDefinition = $field_definition;
    $this->settings = $settings;
    $this->thirdPartySettings = $third_party_settings;
    $this->linkGenerator = $link_generator;
    $this->colorPalette = $colorpalette_utility;
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
      $configuration['third_party_settings'],
      $container->get('link_generator'),
      $container->get('colorpalette.utility')
    );
  }

  /**
   * Fetches filter tags configured at field level.
   *
   * @param bool $load
   *   Load the filter tag object or not.
   *
   * @return object[]|array
   *   An array of filter tag tids or object.
   */
  private function getColorFilterTags(bool $load = TRUE) {
    $filter_tags = $this->getSetting('filter_tags');
    if (!$filter_tags || empty($filter_tags)) {
      return [];
    }

    $tids = $this->colorPalette->extractTargetIds($filter_tags);

    return $load
      ? $this->colorPalette->loadColor($tids)
      : $tids;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $terms = $this->getColorFilterTags();

    $title = $this->t('Filter Tags');
    $element['filter_tags'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['colorpalette_filter_tags']],
      '#title' => $title,
      '#default_value' => !empty($terms) ? $terms : NULL,
      '#description' => $this->t('Filter color by %filter_tags, or leave blank to consider all colors.', [
        '%filter_tags' => $this->linkGenerator->generate(
          $title,
          Url::fromRoute(
            'entity.taxonomy_vocabulary.overview_form',
            ['taxonomy_vocabulary' => 'colorpalette_filter_tags']
          )
        ),
      ]),
      '#tags' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $terms = $this->getColorFilterTags();

    if (empty($terms)) {
      $summary[] = $this->t('Filter tags: @labels', ['@labels' => 'None']);
    }
    else {
      $labels = [];
      foreach ($terms as $term) {
        $labels[] = $term->label();
      }

      $summary[] = $this->t('Filter tags: %labels', ['%labels' => implode(', ', $labels)]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $filter_tags = $this->getColorFilterTags(FALSE);

    $field_definition = $items->getFieldDefinition();
    $field_type = $field_definition->getType();

    $default_value = NULL;
    $referenced_entities = NULL;
    if ($field_type == 'entity_reference') {
      $referenced_entities = $items->referencedEntities();
      if (isset($referenced_entities[$delta])) {
        $default_value = $referenced_entities[$delta];
      }
    }
    else {
      $default_value = isset($items[$delta]->value) ? $items[$delta]->value : NULL;
    }

    // Field attributes.
    $attributes = [
      'hidden' => 'hidden',
      'data-filter-tags' => !empty($filter_tags) ? implode(',', $filter_tags) : 0,
      'data-twig-suggestion' => 'colorpalette',
      'data-field-type' => $field_type,
    ];

    // Build the widget field element based on its type.
    if ($field_type == 'entity_reference') {
      $element['target_id'] = $element + [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'taxonomy_term',
        '#selection_settings' => ['target_bundles' => ['colorpalette_colors']],
        '#default_value' => $default_value,
        '#attributes' => $attributes,
      ];
    }
    else {
      $element['value'] = $element + [
        '#type' => 'textfield',
        '#default_value' => $default_value,
        '#attributes' => $attributes,
      ];
    }

    // Attach the color palette library.
    $element['#attached']['library'] = 'colorpalette/palette';

    return $element;
  }

}
