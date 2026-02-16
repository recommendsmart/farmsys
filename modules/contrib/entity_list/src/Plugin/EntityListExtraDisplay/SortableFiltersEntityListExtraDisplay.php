<?php

namespace Drupal\entity_list\Plugin\EntitylistExtraDisplay;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\entity_list\Controller\EntityListFilterController;
use Drupal\entity_list\Element\RegionTable;
use Drupal\entity_list\Plugin\EntityListExtraDisplayBase;

/**
 * Class SortableFiltersEntityListExtraDisplay.
 *
 * @package Drupal\entity_list\Plugin\EntityListExtraDisplay
 *
 * @EntityListExtraDisplay(
 *   id = "sortable_filters_entity_list_extra_display",
 *   label = @Translation("Sortable Filters entity list extra display")
 * )
 */
class SortableFiltersEntityListExtraDisplay extends EntityListExtraDisplayBase {

  const TYPE = EntityListFilterController::SORTABLE_FILTER_TYPE;

  /**
   * {@inheritdoc}
   */
  public function settingsForm(FormStateInterface $form_state) {
    $form = parent::settingsForm($form_state);

    $form['sortable_contextual'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Sortable Contextual'),
    ];

    $fields = [];

    foreach ($this->contentFilterService->getAllField($this->entity) as $key =>  $field) {
      $fields[]  = $key . ' (' . $field['label'] . ')';
    }

    $form['sortable_contextual']['sort_array'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Sortable Array'),
      '#default_value' => $this->settings['sortable_contextual']['sort_array'] ?? '',
      '#description' => $this->t('You need to set values like: machine_name|sort per row like list_text example: created|ASC. <br> All fields available : @fields', [
        '@fields' => implode(', ', $fields),
      ]),
    ];

    $group_classes = [
      'weight' => 'group-order-weight',
      'region' => 'group-order-region',
    ];

    $regions = $this->buildRegions();

    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings'),
    ];

    $form['settings']['enabled_js'] = [
      '#title' => $this->t('Enabled JS'),
      '#type' => 'checkbox',
      '#default_value' => $this->settings['settings']['enabled_js'] ?? 0,
      '#description' => $this->t('If this is options is enabled the submit of sort form is on click of select element'),
    ];

    $links['add_filters'] = [
      'title' => $this->t('Add Filters'),
      'url' => Url::fromRoute('entity_list.filters_list', [
        'filters_used' => $this->getFiltersUsed(),
        'fields_sortable' => $this->contentFilterService->getFieldSortable($this->entity),
        'entity_list_id' => $this->entity->id(),
        'type' => self::TYPE,
      ]),
      'attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
      ],
    ];

    $form['sortable_filters_exposed'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Sortable Filters Exposed'),
    ];

    $form['sortable_filters_exposed']['operations'] = [
      '#type' => 'operations',
      '#links' => $links,
    ];

    $form['sortable_filters_exposed']['layout_items'] = [
      '#type' => 'region_table',
      '#header' => $this->getLayoutHeader(),
      '#regions' => $regions,
      '#tableselect' => FALSE,
      '#tabledrag' => $this->buildTableDrag($group_classes, $regions),
      '#region_group' => $group_classes['region'],
    ];

    $form['sortable_filters_exposed']['layout_items'] += $this->buildLayoutItems($group_classes, $form_state);

    return $form;
  }

  /**
   * Build layout items.
   *
   * @param array $group_classes
   *   Array of classes.
   * @param FormStateInterface $form_state
   *   This is form state.
   *
   * @return array
   *   Return render array.
   *
   * @throws PluginException
   */
  public function buildLayoutItems(array $group_classes, FormStateInterface $form_state) {
    $form_element = [];

    $default_values = $form_state->getValue([
      'sortable_filter',
      'sortable_filters_exposed',
      'layout_items',
    ], $this->settings['sortable_filters_exposed']['layout_items'] ?? []);

    $layout_items = $this->getAvailableLayoutItems((array) $default_values, $form_state);

    foreach ($layout_items as $key => $layout_item) {
      $label = $layout_item['label'];

      $row = RegionTable::buildRow(
        $label, self::getRegionOptions(),
        [get_class($this), 'getRowRegion'],
        $default_values[$key]['weight'] ?? 0,
        $default_values[$key]['region'] ?? 'disable');

      $row['settings'] = $layout_item['settings'] ?? [];
      $row['#attributes'] = !empty($layout_item['attributes']) ? array_merge($row['#attributes'], $layout_item['attributes']) : $row['#attributes'];

      $row['weight']['#attributes']['class'] = [
        $group_classes['weight'],
        "{$group_classes['weight']}-disable",
      ];

      $row['region']['#attributes']['class'] = [
        $group_classes['region'],
        "{$group_classes['region']}-disable",
      ];

      $form_element[$key] = $row;
    }

    return $form_element;
  }

  /**
   * Get available layout items.
   *
   * @param array $default_values
   *   This is default values.
   * @param FormStateInterface $form_state
   *   This is form state.
   *
   * @return array
   *   Return render array.
   *
   * @throws PluginException
   */
  public function getAvailableLayoutItems(array $default_values, FormStateInterface $form_state) {
    $available_layout = [];
    $filters_used = [];

    foreach ($default_values as $key => $item) {
      if (!empty($item['settings']['type']) && $item['settings']['type'] === self::TYPE) {
        $filters_used[] = $key;
      }
    }

    $field_sortables = $this->contentFilterService->getFieldSortable($this->entity);
    foreach ($this->entityListSortableFilterManager->getDefinitions() as $key => $plugin) {
      if ($key === 'global_entity_list_sortable_filter') {
        foreach ($field_sortables as $field_sortable_key => $field_sortable_element) {
          $field_key = 'global_entity_list_sortable_filter_' . $field_sortable_key;
          if (!empty($field_sortables[$field_sortable_key]) && in_array($field_key, $filters_used)) {
            $this->createFilterAvailableLayoutItem($default_values, $key, $plugin, $available_layout, $field_sortable_element);
          }
        }
      }
      else if (in_array($key, $filters_used, TRUE)) {
        $this->createFilterAvailableLayoutItem($default_values, $key, $plugin, $available_layout);
      }
    }

    return $available_layout;
  }

  /**
   * Create sortable filter available layout item function.
   *
   * @param array $default_values
   *   This is default values.
   * @param string $key
   *   This is key of plugin.
   * @param array $plugin
   *   This is plugin.
   * @param array $availableLayout
   *   This is available layout.
   *
   * @throws PluginException
   */
  private function createFilterAvailableLayoutItem(array $default_values, string $key, array $plugin, array &$availableLayout, array $field_element = []) {
    $instance = $this->entityListSortableFilterManager->createInstance($key);
    $id = !empty($field_element) ? $key . '_' . $field_element['key'] : $key;

    $availableLayout[$id] = [
      'label' => !empty($field_element) ? $field_element['label'] : $plugin['label'],
      'draggable' => TRUE,
      'settings' => [
        'id' => [
          '#type' => 'hidden',
          '#value' => $id,
        ],
        'class' => [
          '#type' => 'hidden',
          '#value' => $plugin['class'],
        ],
        'type' => [
          '#type' => 'hidden',
          '#value' => self::TYPE,
        ],
        'plugin' => [
          '#type' => 'hidden',
          '#value' => $key,
        ],
        'field_name' => [
          '#type' => 'hidden',
          '#value' => $field_element['key'] ?? $plugin['key'],
        ],
      ],
    ];

    $configuration_filter = $instance->configurationFilter($default_values[$id]['settings'] ?? [], $this->entity);

    $configuration_filter['remove'] = [
      '#markup' => Link::createFromRoute($this->t('Remove'), 'entity_list.filters_remove', [
        'filter_id' => $id,
        'entity_list_id' => $this->entity->id(),
        'type' => self::TYPE,
      ], [
        'attributes' => [
          'class' => [
            'button',
            'button--danger'
          ],
        ],
      ])->toString(),
    ];

    $availableLayout[$id]['settings'] = array_merge($availableLayout[$id]['settings'], $configuration_filter);
  }

  /**
   * Get filters used.
   *
   * @return array
   *   Return array of filters current used.
   */
  public function getFiltersUsed() {
    $filters_used = [];

    $layout_items = !empty($this->settings['sortable_filters_exposed']['layout_items']) ? $this->settings['sortable_filters_exposed']['layout_items'] : [];
    foreach ($layout_items as $key => $item) {
      if (!empty($item['settings']['type']) && $item['settings']['type'] === 'sortable_filter') {
        $filters_used[] = $key;
      }
    }

    return $filters_used;
  }

}
