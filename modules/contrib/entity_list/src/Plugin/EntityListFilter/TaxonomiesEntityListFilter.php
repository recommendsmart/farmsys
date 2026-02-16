<?php

namespace Drupal\entity_list\Plugin\EntityListFilter;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\Sql\Query;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_list\Entity\EntityList;
use Drupal\entity_list\Plugin\EntityListFilterBase;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class TaxonomiesEntityListFilter.
 *
 * @package Drupal\entity_list\Plugin
 *
 * @EntityListFilter(
 *   id = "taxonomies_entity_list_filter",
 *   label = @Translation("Taxonomies Entity List Filter"),
 *   content_type = {},
 *   entity_type = {
 *     "node"
 *   },
 * )
 */
class TaxonomiesEntityListFilter extends EntityListFilterBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Language\LanguageManagerInterface definition.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Drupal\Core\Entity\EntityRepositoryInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * TaxonomiesEntityListFilter constructor.
   *
   * @param $configuration
   *   This is configuration.
   * @param $plugin_id
   *   This is plugin id.
   * @param $plugin_definition
   *   This is plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   This is entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   This is language manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   This is entity repository interface.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   This is request stack.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   This is module handler.
   */
  public function __construct(
    $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager,
    EntityRepositoryInterface $entity_repository,
    RequestStack $request_stack,
    ModuleHandler $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->entityRepository = $entity_repository;
    $this->requestStack = $request_stack;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('entity.repository'),
      $container->get('request_stack'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildFilter(array $parameters, EntityList $entity_list) {
    $filter = parent::buildFilter($parameters, $entity_list);
    $terms = $this->getTerms($parameters);
    $options = $this->getOptionsOfTerms($terms);
    $request = $this->requestStack->getCurrentRequest();

    switch ($parameters['settings']['widget']) {
      case 'select':
        $filter[$parameters['settings']['id']] = [
          '#type' => 'select',
          '#multiple' => $parameters['settings']['cardinality'],
          '#title_display' => $parameters['settings']['title_display'] ? 'visible' : 'invisible',
          '#title' => $this->t($parameters['settings']['title']),
          '#empty_option' => $this->t('- Select -'),
          '#default_value' => $request->get($parameters['settings']['id'], []),
          '#options' => $options,
        ];
        break;

      case 'checkbox':
        $filter[$parameters['settings']['id']] = [
          '#type' => $parameters['settings']['cardinality'] ? 'checkboxes' : 'radios',
          '#title_display' => $parameters['settings']['title_display'] ? 'visible' : 'invisible',
          '#title' => $this->t($parameters['settings']['title']),
          '#default_value' => $request->get($parameters['settings']['id'], []),
          '#options' => $options,
        ];
        break;
    }

    if ($parameters['settings']['collapsible'] && $this->moduleHandler->moduleExists('fapi_collapsible')) {
      return $this->addCollapsible(
        $parameters['settings']['title'],
        $filter,
        $parameters['settings']['id'] . 'collapsible',
        !empty(self::convertValues($request->get($parameters['settings']['id'], FALSE))),
        $parameters['settings']['expanded'],
      );
    }

    return $filter;
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

    $configuration['widget'] = [
      '#type' => 'select',
      '#title' => $this->t('Widget'),
      '#required' => TRUE,
      '#options' => [
        'select' => $this->t('Select list'),
        'checkbox' => $this->t('Check boxes/radio buttons'),
      ],
      '#default_value' => $default_value['widget'] ?? '',
    ];

    $configuration['condition_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Condition Type'),
      '#required' => TRUE,
      '#options' => [
        'and' => $this->t('And'),
        'or' => $this->t('Or'),
      ],
      '#default_value' => $default_value['condition_type'] ?? 'and',
      '#description' => $this->t('Condition type between element selected in this filter (or condition need multiple values)'),
    ];

    $configuration['order'] = [
      '#type' => 'select',
      '#title' => $this->t('Order of terms'),
      '#required' => TRUE,
      '#options' => [
        'ASC' => 'asc',
        'DESC' => 'desc',
      ],
      '#default_value' => $default_value['order'] ?? 'ASC',
    ];

    $configuration['exclude'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude terms'),
      '#description' => $this->t('If you check this box, all terms not tagged in node will be not displayed.'),
      '#default_value' => $default_value['exclude'] ?? FALSE,
    ];

    $configuration['cardinality'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Multiple'),
      '#description' => $this->t('If you check this box, you enabled the multiple value for users'),
      '#default_value' => $default_value['cardinality'] ?? FALSE,
    ];

    return $configuration;
  }

  /**
   * Get terms.
   *
   * @param array $parameters
   *   This is parameters of filter.
   *
   * @return array
   *   Return array of terms.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getTerms(array $parameters) {
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $bundles = isset($parameters['settings']['bundles']) ? array_values(json_decode($parameters['settings']['bundles'], TRUE)) : [];

    $connection = Database::getConnection();
    $query = $connection->select('taxonomy_term_data', 't')
      ->fields('t', ['tid']);
    $query->addJoin('inner', 'taxonomy_term_field_data', 'td', 't.tid = td.tid');
    $query->condition('td.vid', $parameters['settings']['field_reference']);
    $query->condition('td.langcode', $langcode);
    $query->orderBy('td.weight', $parameters['settings']['order']);

    if ($parameters['settings']['exclude']) {
      $query->addJoin('right', 'taxonomy_index', 'ti', 'ti.tid = td.tid');
      $query->addJoin('right', 'node', 'n', 'n.nid = ti.nid');
      $query->condition('n.type', $bundles, 'IN');
      $query->groupBy('tid');
    }

    $result = $query->execute()->fetchAll();

    $terms = [];

    foreach ($result as $term) {
      $term = Term::load($term->tid);
      $terms[] = $this->entityRepository->getTranslationFromContext($term, $langcode);
    }

    return $terms;
  }

  /**
   * {@inheritdoc}
   */
  public function setFields(array $settings) {
    $this->fields = [
      [
        'name' => $settings['id'] ?? '',
        'callback_condition' => __CLASS__ . '::taxonomyFilter',
        'field_name' => $settings['field_name'] ?? '',
        'condition_type' => $settings['condition_type'],
      ],
    ];

    return $this->fields;
  }

  /**
   * Taxonomy filter.
   *
   * @param \Drupal\Core\Entity\Query\Sql\Query $query
   *   This is query.
   * @param array $field_settings
   *   This is field settings.
   * @param string|array $field_value
   *   This is field value.
   */
  public static function taxonomyFilter(Query $query, array $field_settings, $field_value) {
    $field_value = self::convertValues($field_value);

    if (!empty($field_value) && $field_settings['condition_type'] === 'or' && is_array($field_value)) {
      $orCondition = $query->orConditionGroup();

      foreach ($field_value as $value) {
        $orCondition->condition($field_settings['field_name'], $value);
      }

      $query->condition($orCondition);
    }
    else if (!empty($field_value)) {
      $query->condition($field_settings['field_name'], $field_value, (is_array($field_value)) ? 'IN' : '=');
    }
  }

  /**
   * Get options of terms.
   *
   * @param array $terms
   *   Array of terms.
   *
   * @return array
   *   Return array of options.
   */
  protected function getOptionsOfTerms(array $terms) {
    $options = [];

    foreach ($terms as $term) {
      $options[$term->id()] = $term->label();
    }

    return $options;
  }

}
