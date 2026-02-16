<?php

namespace Drupal\entity_version\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_version' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_version",
 *   label = @Translation("Version"),
 *   field_types = {
 *     "entity_version"
 *   }
 * )
 */
class EntityVersionFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'minimum_category' => 'patch',
    ] + parent::defaultSettings();
  }

  /**
   * Gets version options available.
   *
   * @return array
   *   The options available.
   */
  protected function getVersionOptions(): array {
    return [
      'major' => $this->t('Major'),
      'minor' => $this->t('Minor'),
      'patch' => $this->t('Patch'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      'minimum_category' => [
        '#type' => 'select',
        '#title' => $this->t('Minimum version'),
        '#description' => $this->t('The minimum version number category to show.'),
        '#default_value' => $this->getSetting('minimum_category'),
        '#options' => $this->getVersionOptions(),
      ],
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $version_options = $this->getVersionOptions();
    $setting_label = $version_options[$this->getSetting('minimum_category')];
    $summary[] = $this->t('Minimum category: @valueLabel', ['@valueLabel' => $setting_label]);
    return parent::settingsSummary();
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
   * Generate the output with the desired version category numbers.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return array
   *   The render array.
   */
  protected function viewValue(FieldItemInterface $item): array {
    $categories = array_keys($this->getVersionOptions());
    $minimum_category = $this->getSetting('minimum_category');
    $text_value = [];

    foreach ($categories as $category) {
      $value = $item->get($category)->getValue();

      $text_value[] = $value;
      if ($category === $minimum_category) {
        $text_value = implode('.', $text_value);
        break;
      }
    }

    return [
      '#markup' => $text_value,
    ];
  }

}
