<?php

namespace Drupal\access_records\Plugin\views\relationship;

use Drupal\access_records\AccessRecordQueryBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns subjects of access records that are of a certain entity type.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("access_record_subject")
 */
class AccessRecordSubject extends RelationshipPluginBase {

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
   * Constructs an AccessRecordTarget object.
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
  public function query() {
    $this->ensureMyTable();
    $ar_entity_type = $this->entityTypeManager->getDefinition('access_record');

    $entity_type_id = $this->definition['subject_type'];
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $id_key = $entity_type->getKey('id');

    $type_storage = $this->entityTypeManager->getStorage('access_record_type');
    $type_ids = $type_storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', TRUE)
      ->condition('subject_type', $entity_type_id)
      ->execute();

    $query_options = ['join_targets' => FALSE];
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
    $def['table'] = $entity_type->getDataTable();
    $def['field'] = 'ar_id';
    $def['left_table'] = $this->tableAlias;
    $def['left_field'] = 'ar_id';
    $def['type'] = empty($this->options['required']) ? 'LEFT' : 'INNER';
    $def['adjusted'] = TRUE;

    $query = array_shift($queries);
    /** @var \Drupal\Core\Database\Query\SelectInterface $union */
    foreach ($queries as $union) {
      $query->union($union);
    }
    $data_query = \Drupal::database()->select($entity_type->getDataTable(), 'data');
    $data_query->join($query, 'id', "[id].[subject_id] = [data].[${id_key}]");
    $data_query->fields('data');
    $data_query->fields('id', ['ar_id']);

    if (empty($this->query->options['disable_sql_rewrite'])) {
      $data_query->addTag($entity_type_id . '_access');
    }
    $def['table formula'] = $data_query;

    $join = \Drupal::service('plugin.manager.views.join')->createInstance('standard', $def);

    $alias = $def['left_table'] . '_' . $def['table'];
    $this->alias = $this->query->addRelationship($alias, $join, $entity_type->getDataTable(), $this->relationship);
  }

}
