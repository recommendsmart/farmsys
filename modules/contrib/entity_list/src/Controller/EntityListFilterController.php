<?php

namespace Drupal\entity_list\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Drupal\entity_list\Entity\EntityList;
use Drupal\entity_list\Plugin\EntitylistExtraDisplay\FiltersEntityListExtraDisplay;
use Drupal\entity_list\Plugin\EntityListFilterManager;
use Drupal\entity_list\Plugin\EntityListSortableFilterManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EntityListFilterController.
 */
class EntityListFilterController extends ControllerBase {

  const SORTABLE_FILTER_TYPE = 'sortableFilter';
  const FILTER_TYPE = 'filter';

  /**
   * This is entity list filter manager.
   *
   * @var \Drupal\entity_list\Plugin\EntityListFilterManager
   */
  protected $entityListFilterManager;

  /**
   * This is form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * This is entity list sortable filter manager.
   *
   * @var \Drupal\entity_list\Plugin\EntityListSortableFilterManager
   */
  protected $entityListSortableFilterManager;

  /**
   * EntityListFilterController constructor.
   *
   * @param \Drupal\entity_list\Plugin\EntityListFilterManager $entity_list_filter_manager
   *   This is entity list filter manager.
   * @param \Drupal\entity_list\Plugin\EntityListSortableFilterManager $entity_list_sortable_filter_manager
   *   This is entity list sortable filter manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   This is form builder.
   */
  public function __construct(EntityListFilterManager $entity_list_filter_manager, EntityListSortableFilterManager $entity_list_sortable_filter_manager, FormBuilderInterface $form_builder) {
    $this->entityListFilterManager = $entity_list_filter_manager;
    $this->entityListSortableFilterManager = $entity_list_sortable_filter_manager;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_list_filter'),
      $container->get('plugin.manager.entity_list_sortable_filter'),
      $container->get('form_builder')
    );
  }

  /**
   * List entity list filter.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   Return render array.
   */
  public function listEntityListFilter(Request $request) {
    $type = $request->query->get('type');

    $headers = [
      ['data' => $this->t('Filter')],
      ['data' => $this->t('Operations')],
    ];

    $rows = [];

    $bundles = $request->query->get('bundles');
    $entity_type_id = $request->query->get('entity_type_id');
    $filters_used = $request->query->get('filters_used') ?? [];
    $fields_sortable = $request->query->get('fields_sortable');
    $terms_filters = $request->query->get('terms_filters');
    $entity_list_id = $request->query->get('entity_list_id');

    if ($type === self::SORTABLE_FILTER_TYPE) {
      foreach ($this->entityListSortableFilterManager->getDefinitions() as $key => $plugin) {
        if (!in_array($key, $filters_used, TRUE)) {
          if ($key === 'global_entity_list_sortable_filter') {
            foreach ($fields_sortable as $field) {
              $row = [];
              $id = $key . '_' . $field['key'];
              $label = $field['label'];
              $this->generateRow($row, $label, $id, $entity_list_id, $key, $type);
              $rows[] = $row;
            }
          }
          else {
            $row = [];
            $this->generateRow($row, $plugin['label'], $key, $entity_list_id, $key, $type);
            $rows[] = $row;
          }
        }
      }
    } else if ($type === self::FILTER_TYPE) {
      foreach ($this->entityListFilterManager->getDefinitions() as $key => $plugin) {
        if (!in_array($key, $filters_used, TRUE)) {
          if (((isset($entity_type_id) && in_array($entity_type_id, $plugin['entity_type']))
              && isset($bundles) && !empty(array_intersect($bundles, $plugin['content_type']))) || empty($plugin['content_type'])) {

            if ($key === 'taxonomies_entity_list_filter') {
              foreach ($terms_filters as $field_filter_key => $field_filter_element) {
                if (!in_array($key . '_' . $field_filter_key, $filters_used)) {
                  $row = [];
                  $id = $key . '_' . $field_filter_key;
                  $label = $plugin['label'] . ' ' . $field_filter_element['label'];
                  $this->generateRow($row, $label, $id, $entity_list_id, $key, $type, $field_filter_element);
                  $rows[] = $row;
                }
              }
            }
            else {
              $row = [];
              $this->generateRow($row, $plugin['label'], $key, $entity_list_id, $key, $type);
              $rows[] = $row;
            }
          }
        }
      }
    }

    return [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No filters available.'),
      '#attributes' => [
        'class' => ['block-add-table'],
      ],
    ];
  }

  /**
   * Add filter.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   Return render array.
   */
  public function addFilter(Request $request) {
    $type = $request->query->get('type');
    $filter_id = $request->query->get('filter_id');
    $entity_list_id = $request->query->get('entity_list_id');
    $plugin_id = $request->query->get('plugin_id');
    $bundle_element = $request->query->get('bundle_element');

    $this->messenger()->addStatus($this->t('The filter has been added'));

    return $this->formBuilder->getForm('Drupal\entity_list\Form\EntityListFilterParametersForm', $type, $entity_list_id, $filter_id, $plugin_id, $bundle_element);
  }

  /**
   * Remove filter.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Return redirect response.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeFilter(Request $request) {
    $type = $request->query->get('type');
    $filter_id = $request->query->get('filter_id');
    $entity_list_id = $request->query->get('entity_list_id');
    $form_name = $type === self::SORTABLE_FILTER_TYPE ? 'sortable_filters_exposed' : 'filters_exposed';
    $property_entity_list = $type === self::SORTABLE_FILTER_TYPE ? 'sortableFilter' : 'filter';
    $entity_list = EntityList::load($entity_list_id);

    if ($entity_list) {
      $filter = $entity_list->get($property_entity_list);
      if (isset($filter[$form_name]['layout_items'][$filter_id])) {
        unset($filter[$form_name]['layout_items'][$filter_id]);
        $entity_list->set($property_entity_list, $filter);
        $entity_list->save();
      }
    }

    $this->messenger()->addStatus($this->t('The filter has been deleted'));

    $url = Url::fromRoute('entity.entity_list.edit_form', ['entity_list' => $entity_list_id], ['absolute' => TRUE]);
    return new RedirectResponse($url->toString());
  }

  /**
   * Generate row.
   *
   * @param array $row
   *   Empty row.
   * @param string $label
   *   The label of row.
   * @param string $id
   *   The id of row.
   * @param string $entity_list_id
   *   The entity list id.
   * @param string $plugin_id
   *   The plugin id.
   * @param string $type
   *   The type of filter.
   * @param null $bundle_element
   *   Array of bundle element.
   */
  public function generateRow(array &$row, string $label, string $id, string $entity_list_id, string $plugin_id, string $type, $bundle_element = null) {
    $row['title']['data'] = [
      '#type' => 'inline_template',
      '#template' => '<div class="block-filter-text-source">{{ label }}</div>',
      '#context' => [
        'label' => $label,
      ],
    ];

    $links['add'] = [
      'title' => $this->t('Add Filter'),
      'url' => Url::fromRoute('entity_list.filters_add', [
        'type' => $type,
        'entity_list_id' => $entity_list_id,
        'filter_id' => $id,
        'plugin_id' => $plugin_id,
        'bundle_element' => $bundle_element,
      ]),
      'attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
      ],
    ];

    $row['operations']['data'] = [
      '#type' => 'operations',
      '#links' => $links,
    ];
  }
}
