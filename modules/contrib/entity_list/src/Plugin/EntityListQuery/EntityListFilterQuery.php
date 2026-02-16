<?php

namespace Drupal\entity_list\Plugin\EntityListQuery;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\entity_list\Plugin\EntityListFilterManager;
use Drupal\entity_list\Plugin\EntityListSortableFilterManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class EntityListFilterQuery.
 *
 * Use a Drupal\Core\Entity\Query\QueryInterface implementation by default.
 *
 * @package Drupal\entity_list\Plugin
 *
 * @EntityListQuery(
 *   id = "filter_entity_list_query",
 *   label = @Translation("Filter Entity Query")
 * )
 */
class EntityListFilterQuery extends DefaultEntityListQuery {

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * This is entity list filter manager;
   *
   * @var \Drupal\entity_list\Plugin\EntityListFilterManager
   */
  protected $entityListFilterManager;

  /**
   * This is entity list sortable filter manager.
   *
   * @var \Drupal\entity_list\Plugin\EntityListSortableFilterManager
   */
  protected $entityListSortableFilterManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $bundle_info,
    LanguageManagerInterface $language_manager,
    RequestStack $request_stack,
    EntityListFilterManager $entity_list_filter_manager,
    EntityListSortableFilterManager $entity_list_sortable_filter_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $bundle_info, $language_manager);
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $bundle_info;
    $this->languageManager = $language_manager;
    $this->query = NULL;
    $this->requestStack = $request_stack;
    $this->entityListFilterManager = $entity_list_filter_manager;
    $this->entityListSortableFilterManager = $entity_list_sortable_filter_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('language_manager'),
      $container->get('request_stack'),
      $container->get('plugin.manager.entity_list_filter'),
      $container->get('plugin.manager.entity_list_sortable_filter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildQuery() {
    parent::buildQuery();

    $filters_contextual = $this->entity->get('filter')['filters_contextual'] ?? [];

    foreach ($filters_contextual as $key => $filter_contextual) {
      if ($filter_contextual) {
        $this->condition($key, TRUE);
      }
    }

    $filter_sortable_contextual = $this->entity->get('sortableFilter')['sortable_contextual']['sort_array'] ?? [];

    if (!empty($filter_sortable_contextual)) {
      $list = explode("\n", $filter_sortable_contextual);

      foreach ($list as $value) {
        $element = explode("|", $value);

        if (!empty($element)) {
          $this->sort($element[0], $element[1]);
        }
      }
    }

    /** @var \Drupal\entity_list\Plugin\EntityListDisplayManager $entity_list_display_manager */
    $display = $this->entity->getEntityListDisplayPlugin();
    $request = $this->requestStack->getCurrentRequest();

    // Prepare fields of filters used in entity list display.
    $fields = [];
    if (method_exists($display, 'asFilters') && method_exists($display, 'getFiltersSettings') && $display->asFilters()) {
      foreach ($display->getFiltersSettings() as $setting) {
        $instance = $this->entityListFilterManager->createInstance($setting['plugin']);

        if (!empty($setting)) {
          $instance_fields = $instance->setFields($setting);
          foreach ($instance_fields as $instance_field) {
            $fields[] = $instance_field;
          }
        }
      }
    }

    // Prepare fields of sortable filter used in entity list display
    $fields_sortable = [];
    if (method_exists($display, 'asSortableFilters') && method_exists($display, 'getSortableFiltersSettings') && $display->asSortableFilters()) {
      foreach ($display->getSortableFiltersSettings() as $setting) {
        $instance = $this->entityListSortableFilterManager->createInstance($setting['plugin']);

        if (!empty($setting)) {
          $instance_fields = $instance->setFields($setting);
          foreach ($instance_fields as $instance_field) {
            $fields_sortable[] = $instance_field;
          }
        }
      }
    }

    // For each form classes, we check the FIELDS constant to know how to
    // Filter in the current list.
    foreach ($fields as $field) {
      if (!empty($field) && !empty($field['name'])) {
        $params = $request->get($field['name'], NULL);
        // Allow filter form to alter the params if needed.
        if (!empty($field['process_params'])) {
          $params = call_user_func_array($field['process_params'], [$params]);
        }
        if (!empty($params)) {
          // Allow filter form to handle the condition itself.
          if (!empty($field['callback_condition'])) {
            call_user_func_array($field['callback_condition'], [
              &$this->query,
              $field,
              $params,
            ]);
          }
          else if (!empty($field['operator'])) {
            $this->condition($field['name'], $params, $field['operator']);
          }
          else {
            $this->condition($field['name'], $params, (is_array($params)) ? 'IN' : '=');
          }
        }
      }
    }

    // Sortable filter in the current list.
    foreach ($fields_sortable as $field) {
      $param = $request->get($field['name'], NULL);

      if (!empty($param)) {
        $this->sort($field['field_name'], $param);
      }
      else if (!empty($field['default_value']) && $field['default_value'] != 'node') {
        $this->sort($field['field_name'], $field['default_value']);
      }
    }
  }

}
