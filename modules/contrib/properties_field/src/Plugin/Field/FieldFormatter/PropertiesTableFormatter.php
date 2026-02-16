<?php

namespace Drupal\properties_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a table formatter for the properties field.
 *
 * @FieldFormatter(
 *   id = "properties_table",
 *   label = @Translation("Properties table"),
 *   field_types = {
 *     "properties"
 *   }
 * )
 */
class PropertiesTableFormatter extends PropertiesFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + [
      'striping' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['striping'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Striping rows'),
      '#default_value' => $this->getSetting('striping'),
    ];

    return parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($this->getSetting('striping')) {
      array_unshift($summary, $this->t('With striping rows'));
    }
    else {
      array_unshift($summary, $this->t('No striping rows'));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    if ($items->isEmpty()) {
      return [];
    }

    $rows = [];
    foreach ($items as $delta => $item) {
      $rows[$delta]['data'][] = [
        'header' => TRUE,
        'data' => $item->label,
      ];

      $plugin = $this->getValueTypePlugin($item->type);
      $value = $plugin ? $plugin->formatterRender($item->value) : '';

      $rows[$delta]['data'][] = $value;
    }

    return [
      [
        '#theme' => 'table',
        '#rows' => $rows,
        '#no_striping' => !$this->getSetting('striping'),
      ]
    ];
  }

}
