<?php

namespace Drupal\number_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use NumberFormatter as IntlNumberFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Random_default' formatter.
 *
 * @FieldFormatter(
 *   id = "number_formatter",
 *   label = @Translation("Number Formatter"),
 *   field_types = {
 *     "decimal",
 *     "float",
 *     "integer"
 *   }
 * )
 */
class NumberFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  const LANGUAGE_SELECT_CURRENT = 'current';
  const LANGUAGE_SELECT_FIELD = 'field';

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a NumberFormatter instance.
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
   *   Any third party settings settings.
   * @param \Drupal\Core\LanguageManagerInterface $language_manager
   *   The entity manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, LanguageManagerInterface $language_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->languageManager = $language_manager;
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
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $styleDisplayName = $this->styleDisplayName($this->settings['style']);

    $summary = [];
    $summary[] = $this->t('Style: :style.', [':style' => $styleDisplayName]);

    if ($this->isCurrencyStyle() && !empty($this->settings['currency'])) {
      $summary[] = $this->t('Currency: :currency.', [':currency' => $this->settings['currency']]);
    }

    if ($this->languageManager->isMultilingual()) {
      $summary[] = $this->t('Language: :lang.', [':lang' => $this->settings['lang_select']]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    switch ($this->settings['style']) {
      case self::LANGUAGE_SELECT_FIELD:
        $language = $langcode;
        break;

      case self::LANGUAGE_SELECT_CURRENT:
      default:
        $language = $this->languageManager->getCurrentLanguage()->getId();
        break;
    }

    $numberFormatter = new IntlNumberFormatter($language, $this->settings['style']);

    $element = [];
    foreach ($items as $delta => $item) {
      // Render each element as markup.
      if ($this->isCurrencyStyle()) {
        $element[$delta] = ['#markup' => $numberFormatter->formatCurrency($item->value, $this->settings['currency'])];
      }
      else {
        $settings = $this->getFieldSettings();

        $prefixes = isset($settings['prefix']) ? array_map(['Drupal\Core\Field\FieldFilteredMarkup', 'create'], explode('|', $settings['prefix'])) : [''];
        $suffixes = isset($settings['suffix']) ? array_map(['Drupal\Core\Field\FieldFilteredMarkup', 'create'], explode('|', $settings['suffix'])) : [''];
        $prefix = (count($prefixes) > 1) ? $this->formatPlural($item->value, $prefixes[0], $prefixes[1]) : $prefixes[0];
        $suffix = (count($suffixes) > 1) ? $this->formatPlural($item->value, $suffixes[0], $suffixes[1]) : $suffixes[0];

        $element[$delta] = ['#markup' => $prefix . $numberFormatter->format($item->value) . $suffix];
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'style' => (string) IntlNumberFormatter::DECIMAL,
      'currency' => '',
      'lang_select' => self::LANGUAGE_SELECT_CURRENT,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   *
   * AJAX functionality for toggling the currency is based on
   * https://www.drupal.org/docs/8/creating-custom-modules/create-a-custom-field-formatter.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['style'] = [
      '#title' => $this->t('Style'),
      '#type' => 'select',
      '#options' => $this->styleOptions(),
      '#default_value' => $this->getSetting('style'),
      '#ajax' => [
        'wrapper' => 'private_message_thread_member_formatter_settings_wrapper',
        'callback' => [$this, 'ajaxCallback'],
      ],
    ];

    $element['currency'] = [
      '#prefix' => '<div id="private_message_thread_member_formatter_settings_wrapper">',
      '#suffix' => '</div>',
    ];

    // First, retrieve the field name for the current field].
    $field_name = $this->fieldDefinition->getItemDefinition()->getFieldDefinition()->getName();
    // Next, set the key for the setting for which a value is to be retrieved.
    $setting_key = 'style';

    // Try to retrieve a value from the form state. This will not exist on
    // initial page load.
    $value = $form_state->getValue([
      'fields',
      $field_name,
      'settings_edit_form',
      'settings',
      $setting_key,
    ]);

    if (is_numeric($value)) {
      $style = $value;
    }
    // On initial page load, retrieve the default setting.
    else {
      $style = $this->getSetting('style');
    }

    if ($this->isCurrencyStyle($style)) {
      $element['currency']['#type'] = 'textfield';
      $element['currency']['#size'] = 8;
      $element['currency']['#title'] = $this->t('Currency');
      $element['currency']['#default_value'] = $this->getSetting('currency');
    }
    else {
      // Force the element to render (so that the AJAX wrapper is rendered) even
      // When no value is selected.
      $element['currency']['#markup'] = '';
    }

    if ($this->languageManager->isMultilingual()) {
      $element['lang_select'] = [
        '#title' => $this->t('Use language from'),
        '#type' => 'radios',
        '#options' => [
          self::LANGUAGE_SELECT_FIELD => $this->t('Field language'),
          self::LANGUAGE_SELECT_CURRENT => $this->t('Current language'),
        ],
        '#default_value' => $this->getSetting('lang_select'),
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxCallback(array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getItemDefinition()->getFieldDefinition()->getName();
    $element_to_return = 'currency';

    return $form['fields'][$field_name]['plugin']['settings_edit_form']['settings'][$element_to_return];
  }

  /**
   * Helper to get style options.
   *
   * @return array
   *   An array of style options keyed by their machine name and with
   *   translated, display names as values.
   */
  protected function styleOptions(): array {
    return [
      IntlNumberFormatter::PATTERN_DECIMAL => $this->t('Pattern Decimal'),
      IntlNumberFormatter::DECIMAL => $this->t('Decimal Format'),
      IntlNumberFormatter::CURRENCY => $this->t('Currency'),
      IntlNumberFormatter::PERCENT => $this->t('Percent'),
      IntlNumberFormatter::SCIENTIFIC => $this->t('Scientific'),
      IntlNumberFormatter::SPELLOUT => $this->t('Spellout'),
      IntlNumberFormatter::ORDINAL => $this->t('Ordinal'),
      IntlNumberFormatter::DURATION => $this->t('Duration'),
    ];
  }

  /**
   * Helper for getting the translated, display name of a style.
   *
   * @param string $style
   *   A style as defined by the intl extension NumberFormatter class.
   *
   * @return string
   *   The translated display name of the style.
   */
  protected function styleDisplayName(string $style): string {
    $styles = $this->styleOptions();
    return $styles[$style];
  }

  /**
   * Helper for determining if a style is a currency style.
   *
   * @param string|null $style
   *   (optional) The style to check. Defaults to the style set in settings.
   *
   * @return bool
   *   Returns TRUE if the style is a currency style.
   */
  protected function isCurrencyStyle(?string $style = NULL): bool {
    if (is_null($style)) {
      $style = $this->settings['style'];
    }

    return ($style == IntlNumberFormatter::CURRENCY);
  }

}
