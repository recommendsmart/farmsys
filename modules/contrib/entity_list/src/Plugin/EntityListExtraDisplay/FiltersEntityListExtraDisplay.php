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
 * Class FiltersEntityListExtraDisplay.
 *
 * @package Drupal\entity_list\Plugin\EntityListExtraDisplay
 *
 * @EntityListExtraDisplay(
 *   id = "filters_entity_list_extra_display",
 *   label = @Translation("Filters entity list extra display")
 * )
 */
class FiltersEntityListExtraDisplay extends EntityListExtraDisplayBase {

  const TYPE = EntityListFilterController::FILTER_TYPE;

  const FILTERS_CONTEXTUAL = [
    'status' => 'Publish',
    'promote' => 'Promoted to front page',
    'sticky' => 'Sticky',
  ];

  /**
   * {@inheritdoc}
   */
  public function settingsForm(FormStateInterface $form_state) {
    $form = parent::settingsForm($form_state);
    $data = $this->getDataAddFilters($form_state);

    $form['filters_contextual'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Filters Contextual'),
    ];

    foreach (self::FILTERS_CONTEXTUAL as $key => $filter_contextual) {
      $form['filters_contextual'][$key] = [
        '#type' => 'checkbox',
        '#title' => $this->t($filter_contextual),
        '#default_value' => $this->settings['filters_contextual'][$key] ?? 0,
      ];
    }

    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings'),
    ];

    $form['settings']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $this->settings['settings']['title'] ?? '',
    ];

    $form['settings']['submit_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Submit Title'),
      '#default_value' => $this->settings['settings']['submit_title'] ?? '',
    ];

    $form['settings']['class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class'),
      '#default_value' => $this->settings['settings']['class'] ?? '',
    ];

    $form['filters_exposed'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Filters Exposed'),
    ];

    $form['filters_exposed']['description'] = [
      '#markup' => '<p>' . $this->t('You need to use Filter entity list display for this settings') . '</p>',
    ];

    $links['add_filters'] = [
      'title' => $this->t('Add Filters'),
      'url' => Url::fromRoute('entity_list.filters_list', [
        'bundles' => $data['selected_bundles'],
        'entity_type_id' => $data['entity_type_id'],
        'filters_used' => $data['filters_used'],
        'terms_filters' => $data['terms_filters'],
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

    $form['filters_exposed']['operations'] = [
      '#type' => 'operations',
      '#links' => $links,
    ];

    $group_classes = [
      'weight' => 'group-order-weight',
      'region' => 'group-order-region',
    ];

    $regions = $this->buildRegions();

    $form['filters_exposed']['layout_items'] = [
      '#type' => 'region_table',
      '#header' => $this->getLayoutHeader(),
      '#regions' => $regions,
      '#tableselect' => FALSE,
      '#tabledrag' => $this->buildTableDrag($group_classes, $regions),
      '#region_group' => $group_classes['region'],
    ];

    $form['filters_exposed']['layout_items'] += $this->buildLayoutItems($group_classes, $form_state);

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
      'filter',
      'filters_exposed',
      'layout_items',
    ], $this->settings['filters_exposed']['layout_items'] ?? []);

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
   * Check filters used in array.
   *
   * @param $key
   *   This is current key.
   * @param $filter_keys
   *   This filter keys used in entity list.
   *
   * @return bool
   *   Return boolean.
   */
  public function checkFiltersUsed($key, $filter_keys) {
    $found = FALSE;

    foreach ($filter_keys as $filter_key) {
      if (str_contains($filter_key, $key)) {
        $found = TRUE;
      }
    }

    return $found;
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
    $availableLayout = [];

    $plugins_definitions = $this->entityListFilterManager->getDefinitions();
    $fields_as_terms = $this->contentFilterService->getFieldsTermsFilter($this->entity);

    foreach ($default_values as $key => $item) {
      if (!empty($item['settings']['type']) && $item['settings']['type'] === self::TYPE) {
          if (!empty($plugins_definitions[$key])) {
            $this->createFilterAvailableLayoutItem($default_values, $key, $plugins_definitions[$key], $availableLayout);
          }
          else if (str_contains($key, 'taxonomies_entity_list_filter')) {
            foreach ($fields_as_terms as $field_filter_key => $field_filter_element) {
              if ($key === 'taxonomies_entity_list_filter_' . $field_filter_key) {
                $this->createFilterAvailableLayoutItem(
                  $default_values,
                  'taxonomies_entity_list_filter',
                  $plugins_definitions['taxonomies_entity_list_filter'],
                  $availableLayout,
                  $field_filter_key,
                  $field_filter_element,
                );
              }
            }
          }
          else if (str_contains($key, 'custom_list_entity_list_filter')) {
            $this->createFilterAvailableLayoutItem(
              $default_values,
              'custom_list_entity_list_filter',
              $plugins_definitions['custom_list_entity_list_filter'],
              $availableLayout,
              $this->getCustomFilterId('custom_list_entity_list_filter', $key)
            );
          }
      }
    }

    return $availableLayout;
  }

  /**
   * Get Custom Filter Id.
   *
   * @param string $plugin_key
   *   This is plugin key.
   * @param string $key
   *   This is key.
   *
   * @return string
   *   Return id of custom filter.
   */
  public function getCustomFilterId(string $plugin_key, string $key) {
    return substr($key, strlen($plugin_key) + 1, strlen($key) - strlen($plugin_key));
  }

  /**
   * Create filter available layout item function.
   *
   * @param array $default_values
   *   This is default values.
   * @param string $key
   *   This is key of plugin.
   * @param array $plugin
   *   This is plugin.
   * @param array $availableLayout
   *   This is available layout.
   * @param string $bundle_key
   *   This is bundle key of filter.
   * @param array $bundle_element
   *   This is bundle name of filter.
   *
   * @throws PluginException
   */
  private function createFilterAvailableLayoutItem(array $default_values, string $key, array $plugin, array &$availableLayout, $bundle_key = null, $bundle_element = null) {
    $instance = $this->entityListFilterManager->createInstance($key);
    $id = $bundle_key ? $key . '_' . $bundle_key : $key;

    $availableLayout[$id] = [
      'label' => isset($bundle_element['label']) ? $plugin['label'] . ' ' .  $bundle_element['label']: $plugin['label'],
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
        'bundles' => [
          '#type' => 'hidden',
          '#value' => isset($bundle_element['bundles']) ? json_encode($bundle_element['bundles']) : '',
        ],
        'field_reference' => [
          '#type' => 'hidden',
          '#value' => isset($bundle_element['vocabulary']) && is_string($bundle_element['vocabulary']) ? $bundle_element['vocabulary'] : '',
        ],
        'field_name' => [
          '#type' => 'hidden',
          '#value' => isset($bundle_element['key']) && is_string($bundle_element['key']) ? $bundle_element['key'] : '',
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
   * Get data add filters.
   *
   * @param FormStateInterface $form_state
   *
   * @return array
   */
  public function getDataAddFilters(FormStateInterface $form_state) {
    $selected_bundles = [];
    $entity_type_id = 0;
    $filters_used = [];

    $selected_query_plugin = $form_state->getValue(['query', 'plugin']);
    if ($query_plugin = $this->entity->getEntityListQueryPlugin($selected_query_plugin)) {
      $selected_bundles = array_filter($query_plugin->getBundles());
      $entity_type_id = $query_plugin->getEntityTypeId();
    }

    $layout_items = !empty($this->settings['filters_exposed']['layout_items']) ? $this->settings['filters_exposed']['layout_items'] : [];
    foreach ($layout_items as $key => $item) {
      if (!empty($item['settings']['type']) && $item['settings']['type'] === self::TYPE) {
        $filters_used[] = $key;
      }
    }

    $terms_filters = $this->contentFilterService->getFieldsTermsFilter($this->entity);

    return [
      'selected_bundles' => $selected_bundles,
      'entity_type_id' => $entity_type_id,
      'filters_used' => $filters_used,
      'terms_filters' => $terms_filters,
    ];
  }

}
