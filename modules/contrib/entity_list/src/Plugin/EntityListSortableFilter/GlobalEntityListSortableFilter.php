<?php

namespace Drupal\entity_list\Plugin\EntityListSortableFilter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_list\Annotation\EntityListSortableFilter;
use Drupal\entity_list\Entity\EntityList;
use Drupal\Core\Entity\Query\Sql\Query;
use Drupal\entity_list\Plugin\EntityListSortableFilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class GlobalEntityListSortableFilter.
 *
 * @package Drupal\entity_list\Plugin
 *
 * @EntityListSortableFilter(
 *   id = "global_entity_list_sortable_filter",
 *   label = @Translation("Global Entity List Sortable Filter"),
 * )
 */
class GlobalEntityListSortableFilter extends EntityListSortableFilterBase implements ContainerFactoryPluginInterface {

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack RequestStack
   */
  protected $requestStack;

  /**
   * Constructs GlobalEntityListSortableFilter object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service used to get url query parameters.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildFilter(array $parameters, EntityList $entity_list) {
    $filters = parent::buildFilter($parameters, $entity_list);
    $request = $this->requestStack->getCurrentRequest();

    $filters[$parameters['settings']['id']] = [
      '#type' => 'select',
      '#title' => $this->t($parameters['settings']['title']),
      '#default_value' => $request->get($parameters['settings']['id'], $parameters['settings']['default_value'] ?? 'ASC'),
      '#options' => [
        'ASC' => $this->t(!empty($parameters['settings']['asc_title']) ? $parameters['settings']['asc_title'] : 'ASC'),
        'DESC' => $this->t(!empty($parameters['settings']['desc_title']) ? $parameters['settings']['desc_title'] : 'DESC'),
      ],
      '#attributes' => [
        'data-field-name' => $parameters['settings']['id'],
        'class' => [
          'sortable-filters',
        ],
      ],
    ];

    return $filters;
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFilter(array $default_value, EntityList $entity_list) {
    $configuration = parent::configurationFilter($default_value, $entity_list);

    $configuration['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $default_value['title'] ?? '',
      '#required' => TRUE,
    ];

    $configuration['asc_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ASC Title'),
      '#default_value' => $default_value['asc_title'] ?? '',
    ];

    $configuration['desc_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('DESC Title'),
      '#default_value' => $default_value['desc_title'] ?? '',
    ];

    $configuration['default_value'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Value'),
      '#default_value' => $default_value['default_value'] ?? '',
      '#options' => [
        'none' => $this->t('- None -'),
        'ASC' => $this->t('ASC'),
        'DESC' => $this->t('DESC'),
      ],
    ];

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setFields(array $settings) {
    $this->fields = [
      [
        'name' => $settings['id'] ?? '',
        'callback_condition' => __CLASS__ . '::sort',
        'field_name' => $settings['field_name'] ?? '',
        'default_value' => $settings['default_value'] ?? '',
      ],
    ];

    return $this->fields;
  }

  /**
   * Sort filter.
   *
   * @param Query $query
   *   This is query.
   * @param $fieldSettings
   *   This is field settings.
   * @param $fieldValue
   *   This is field value.
   */
  public function sort(Query $query, $fieldSettings, $fieldValue) {
    $query->sort($fieldSettings['field_name'], $fieldValue);
  }
}
