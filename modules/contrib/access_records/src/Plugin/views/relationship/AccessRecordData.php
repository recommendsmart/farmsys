<?php

namespace Drupal\access_records\Plugin\views\relationship;

use Drupal\access_records\AccessRecordQueryBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns access records that use the main entity of the View.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("access_record_data")
 */
class AccessRecordData extends RelationshipPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The access record query builder.
   *
   * @var \Drupal\access_records\AccessRecordQueryBuilder
   */
  protected AccessRecordQueryBuilder $accessRecordQueryBuilder;

  /**
   * Constructs an AccessRecordData object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $etm
   *   The entity type manager.
   * @param \Drupal\access_records\AccessRecordQueryBuilder $arqb
   *   The access record query builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $etm, AccessRecordQueryBuilder $arqb) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $etm;
    $this->accessRecordQueryBuilder = $arqb;
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
      $container->get('access_records.query_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['types'] = ['default' => []];
    $options['match'] = ['default' => 'target'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $type_storage = $this->entityTypeManager->getStorage('access_record_type');
    $type_query = $type_storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', TRUE);

    $options = [];
    foreach ($type_storage->loadMultiple($type_query->execute()) as $ar_type) {
      $options[$ar_type->id()] = $ar_type->label();
    }
    $form['types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Access record types'),
      '#options' => $options,
      '#default_value' => $this->options['types'] ?? NULL,
      '#description' => $this->t('Choose which types of access records you wish to relate. When none is chosen, all types will be included.'),
    ];
    $form['match'] = [
      '#type' => 'select',
      '#title' => $this->t('Match criteria'),
      '#description' => $this->t('Choose whether the matching criteria should be based on subjects (mostly users) or targets (mostly content).'),
      '#options' => [
        'subject' => $this->t('Subject'),
        'target' => $this->t('Target'),
      ],
      '#default_value' => $this->options['match'] ?? 'target',
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    // Transform the #type = checkboxes value to a numerically indexed array,
    // because the config schema expects a sequence, not a mapping.
    $type_ids = $form_state->getValue(['options', 'types']);
    $form_state->setValue(['options', 'types'], array_values(array_filter($type_ids)));
    $this->options['match'] = $form_state->getValue(['options', 'match'], 'target');
    parent::submitOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $ar_entity_type = $this->entityTypeManager->getDefinition('access_record');

    $entity_type_id = $this->definition['entity_type'];
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $field = $entity_type->getKey('id');

    $type_storage = $this->entityTypeManager->getStorage('access_record_type');
    $selected_types = array_filter($this->options['types']);
    $type_ids = $selected_types ? $type_storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', TRUE)
      ->condition('id', $selected_types, 'IN')->execute() : NULL;

    $match_criteria = $this->options['match'] ?? 'target';
    $query_options = [];
    if ($match_criteria === 'subject') {
      $query_options['join_targets'] = FALSE;
    }
    elseif ($match_criteria === 'target') {
      $query_options['join_subjects'] = FALSE;
    }
    $queries = [];
    /** @var \Drupal\access_records\AccessRecordTypeInterface $ar_type */
    foreach ($type_storage->loadMultiple($type_ids) as $ar_type) {
      if ($query = $this->accessRecordQueryBuilder->selectByType($ar_type, NULL, 'view', $query_options)) {
        $queries[$ar_type->id()] = $query;
      }
    }
    if (empty($queries)) {
      // Use a query whose result is always empty. It is safe to use the ar_id
      // for subject and target too, as we never get a row from here.
      $query = \Drupal::database()->select($ar_entity_type->getDataTable(), 'id')
        ->alwaysFalse(TRUE);
      $query->addField('id', 'ar_id', 'ar_id');
      $query->addField('id', 'ar_id', 'subject_id');
      $query->addField('id', 'ar_id', 'target_id');
      $queries[] = $query;
    }

    $def = $this->definition;
    $def['table'] = $ar_entity_type->getDataTable();
    $def['field'] = $match_criteria . '_id';
    $def['left_table'] = $this->tableAlias;
    $def['left_field'] = $field;
    $def['type'] = empty($this->options['required']) ? 'LEFT' : 'INNER';
    $def['adjusted'] = TRUE;
    if ($entity_type_id) {
      $def['extra'] = [
        [
          'field' => 'ar_' . $match_criteria . '_type',
          'value' => $entity_type_id,
          'operator' => '=',
        ],
      ];
    }

    $query = array_shift($queries);
    /** @var \Drupal\Core\Database\Query\SelectInterface $union */
    foreach ($queries as $union) {
      $query->union($union);
    }
    $data_query = \Drupal::database()->select($ar_entity_type->getDataTable(), 'data');
    $data_query->join($query, 'id', "[id].[ar_id] = [data].[ar_id]");
    $data_query->fields('data');
    $data_query->fields('id', [$match_criteria . '_id']);

    if (empty($this->query->options['disable_sql_rewrite'])) {
      $data_query->addTag('access_record_access');
    }
    $def['table formula'] = $data_query;

    $join = \Drupal::service('plugin.manager.views.join')->createInstance('standard', $def);

    $alias = $def['left_table'] . '_' . $def['table'];
    $this->alias = $this->query->addRelationship($alias, $join, $ar_entity_type->getDataTable(), $this->relationship);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    $type_storage = $this->entityTypeManager->getStorage('access_record_type');
    foreach ($this->options['types'] as $type_id) {
      if ($type = $type_storage->load($type_id)) {
        $dependencies[$type->getConfigDependencyKey()][] = $type->getConfigDependencyName();
      }
    }

    return $dependencies;
  }

}
