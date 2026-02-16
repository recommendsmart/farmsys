<?php

namespace Drupal\entity_list\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_list\Entity\EntityList;

/**
 * Class FilterFormBase.
 */
abstract class EntityListFilterBase extends PluginBase implements EntityListFilterInterface {

  use StringTranslationTrait;

  /**
   * @var array
   */
  public array $fields = [];

  /**
   * {@inheritdoc}
   */
  public function buildFilter(array $parameters, EntityList $entity_list) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setFields(array $settings) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFilter(array $default_value, EntityList $entity_list) {
    $form = [
      '#type' => 'details',
      '#title' => $this->t('Settings'),
      '#open' => FALSE,
      '#tree' => TRUE,
    ];

    $form['collapsible'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Collapsible'),
      '#default_value' => $default_value['collapsible'] ?? FALSE,
      '#weight' => 48,
    ];

    $form['expanded'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expanded by default'),
      '#default_value' => $default_value['expanded'] ?? FALSE,
      '#weight' => 49,
    ];

    $form['title_display'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display title'),
      '#default_value' => $default_value['title_display'] ?? FALSE,
      '#weight' => 50,
    ];

    return $form;
  }

  /**
   * Add collapsible.
   *
   * @param string $title
   *   This is title.
   * @param array $filter
   *   This is filter.
   * @param string $id
   *   This is id.
   * @param bool $asValue
   *   This is as value boolean.
   * @param bool $expanded
   *   This is expanded boolean.
   *
   * @return array
   */
  public function addCollapsible(string $title, array $filter, string $id, bool $asValue, bool $expanded) {
    $filter_collapsible[$id] = [
      '#type' => 'collapsible',
      '#title' => $this->t($title),
      '#name' => 'filter',
      '#expanded' => $expanded || $asValue,
    ];

    $final_filter[$id] = array_merge($filter_collapsible[$id], $filter);

    return $final_filter;
  }

  /**
   * Convert values.
   *
   * @param array|mixed $values
   *   This is current values.
   *
   * @return array|mixed
   *   Return converted values.
   */
  public static function convertValues(mixed $values) {
    if (is_array($values)) {
      foreach ($values as $key => $value) {
        if ($value == 0) {
          unset($values[$key]);
        }
      }
    }

    return $values;
  }

}
