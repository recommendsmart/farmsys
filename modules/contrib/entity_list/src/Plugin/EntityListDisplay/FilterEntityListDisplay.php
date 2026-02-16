<?php

namespace Drupal\entity_list\Plugin\EntityListDisplay;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FilterEntityListDisplay.
 *
 * Override the getAvailableLayoutItems() method if you want to add, modify or
 * delete exposed items in the layout table (admin ui).
 * Make sure to take into account your changes in the
 * getRenderedLayoutItems|getRenderedItems|render (order by priority) methods
 * and override it if needed.
 *
 * This plugin depends on a EntityListQuery plugin
 *
 * @package Drupal\entity_list\Plugin\EntityListDisplay
 *
 * @EntityListDisplay(
 *   id = "filter_entity_list_display",
 *   label = @Translation("Filter entity list display")
 * )
 */
class FilterEntityListDisplay extends DefaultEntityListDisplay {

  /**
   * This is form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * FilterEntityListDisplay constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository to find available view mode.
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $layout_plugin_manager
   *   The layout plugin manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $entity_display_repository,
    LayoutPluginManagerInterface $layout_plugin_manager,
    FormBuilderInterface $form_builder) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager,
      $entity_display_repository,
      $layout_plugin_manager);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('plugin.manager.core.layout'),
      $container->get('form_builder')
    );
  }

  /**
   * Get the available layout items.
   *
   * @param array $default_values
   *   The default values from the form_state object or from the saved settings.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   An array representing the available items in the layout table.
   */
  protected function getAvailableLayoutItems(array $default_values, FormStateInterface $form_state) {
    $availableLayout = parent::getAvailableLayoutItems($default_values, $form_state);
    $availableLayout['filters'] = [
      'label' => $this->t('Filters'),
      'draggable' => TRUE,
    ];

    $availableLayout['sortable_filters'] = [
      'label' => $this->t('Sortable Filters'),
      'draggable' => TRUE,
    ];

    return $availableLayout;
  }

  /**
   * Get the rendered layout items per region.
   *
   * @param array $entities
   *   The current entities.
   * @param array $layout_items
   *   The layout items settings.
   *
   * @return array
   *   An array representing the layout items per region. Ready to be used with
   *   the layout's build method.
   */
  public function getRenderedLayoutItems(array $entities, array $layout_items) {
    // Group by region to match the layout build method.
    $rendered_items = [];

    $items = array_filter($layout_items, function ($layout_item) {
      return $layout_item['region'] !== 'disable';
    });

    $query_plugin = $this->entity->getEntityListQueryPlugin();

    foreach ($items as $key => $item) {
      $rendered_item = [];
      switch ($key) {
        case 'pager_1':
        case 'pager_2':
          if ($query_plugin->usePager()) {
            $rendered_item = [
              '#type' => 'pager',
            ];
          }
          break;

        case 'total':
          if (!empty($entities) && $query_plugin->usePager()) {
            $pager_info = entity_list_get_pager_infos();
            $rendered_item = [
              '#plain_text' => $this->formatPlural(
                $pager_info['total'] ?? 0,
                !empty($item['settings']['singular']) ? $item['settings']['singular'] : '1 item',
                !empty($item['settings']['plural']) ? $item['settings']['plural'] : '@count items',
              ),
              '#prefix' => '<p class="total">',
              '#suffix' => '</p>',
            ];
          }
          break;

        case 'items':
          if (!empty($entities)) {
            $attr = new Attribute();
            $attr->addClass('entity-list-item');
            if (!empty($item['settings']['custom_class_item'])) {
              $attr->addClass(explode(' ', $item['settings']['custom_class_item']));
            }

            $rendered_item = [];
            $query_plugin = $this->entity->getEntityListQueryPlugin();
            $view_builder = $this->entityTypeManager->getViewBuilder($query_plugin->getEntityTypeId());
            $values = $this->getLayoutItems();

            foreach ($entities as $entity_key => $entity) {
              $rendered_item[$entity_key] = [
                '#theme' => 'entity_list_item',
                '#attributes' => $attr,
                '#element' => $view_builder->view($entity, $values['items']['settings']['view_mode'] ?? ''),
                '#list_id' => $this->entity->id(),
              ];
            }
          }
          else if (!empty($item['settings']['empty'])) {
            $rendered_item = [
              '#plain_text' => $this->t($item['settings']['empty']),
            ];
          }
          if (!empty($item['settings']['custom_class'])) {
            $attributes = new Attribute();
            $attributes->addClass(explode(' ', $item['settings']['custom_class']));
            $rendered_items[$item['region']]['#attributes'] = $attributes;
          }
          $rendered_items[$item['region']]['#tag'] = !empty($entities) ? 'ul' : 'p';
          break;

        case 'filters':
          $params = [];

          if (!empty($this->entity->get('filter')['filters_exposed']['layout_items'])) {
            foreach ($this->entity->get('filter')['filters_exposed']['layout_items'] as $element_key => $element) {
              if ($element['region'] === 'filters') {
                $params[$element_key] = $element;
              }
            }
          }

          $filters = array_keys($params ?? []);

          if (!empty($filters)) {
            $rendered_item = $this->formBuilder->getForm(
              'Drupal\entity_list\Form\EntityListFilterForm',
              $this->entity,
              $filters,
              $params
            );
          }
          break;

        case 'sortable_filters':
          $params = [];

          if (!empty($this->entity->get('sortableFilter')['sortable_filters_exposed']['layout_items'])) {
            foreach ($this->entity->get('sortableFilter')['sortable_filters_exposed']['layout_items'] as $element_key => $element) {
              if ($element['region'] === 'filters') {
                $params[$element_key] = $element;
              }
            }
          }

          $filters = array_keys($params ?? []);

          if (!empty($filters)) {
            $rendered_item = $this->formBuilder->getForm(
              'Drupal\entity_list\Form\EntityListSortableFilterForm',
              $this->entity,
              $filters,
              $params
            );
          }
          break;

      }
      $rendered_items[$item['region']][$key] = $rendered_item;
    }

    return $rendered_items;
  }

  /**
   * Get the filters settings.
   *
   * @return array
   *   The filters settings.
   */
  public function getFiltersSettings() {
    $form_fields = [];

    if (!empty($this->entity->get('filter')['filters_exposed']['layout_items'])) {
      foreach ($this->entity->get('filter')['filters_exposed']['layout_items'] as $item) {
        if ($item['region'] === 'filters') {
          $form_fields[] = $item['settings'];
        }
      }
    }

    return $form_fields;
  }

  /**
   * Get the sortable filters settings.
   *
   * @return array
   *   The sortable filters settings.
   */
  public function getSortableFiltersSettings() {
    $form_fields = [];

    if (!empty($this->entity->get('sortableFilter')['sortable_filters_exposed']['layout_items'])) {
      foreach ($this->entity->get('sortableFilter')['sortable_filters_exposed']['layout_items'] as $item) {
        if ($item['region'] === 'filters') {
          $form_fields[] = $item['settings'];
        }
      }
    }

    return $form_fields;
  }

  /**
   * As filters.
   *
   * @return bool
   *   Return boolean as filters.
   */
  public function asFilters() {
    $as_filter = FALSE;

    foreach ($this->settings['layout_items'] as $key => $item) {
      if ($item['region'] != 'disable' && $key === 'filters') {
        $as_filter = TRUE;
      }
    }

    return $as_filter;
  }
  /**
   * As sortable filters.
   *
   * @return bool
   *   Return boolean as sortable filters.
   */

  public function asSortableFilters() {
    $as_sortable_filter = FALSE;

    foreach ($this->settings['layout_items'] as $key => $item) {
      if ($item['region'] != 'disable' && $key === 'sortable_filters') {
        $as_sortable_filter = TRUE;
      }
    }

    return $as_sortable_filter;
  }

  /**
   * {@inheritdoc}
   */
  public function render(array $items, $view_mode = 'full', $langcode = NULL) {
    $render = parent::render($items, $view_mode, $langcode);
    $render['#cache']['contexts'][] = 'url';
    return $render;
  }

}
