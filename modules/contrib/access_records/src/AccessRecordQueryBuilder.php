<?php

namespace Drupal\access_records;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\Sql\Query;
use Drupal\Core\Session\AccountInterface;

/**
 * Builds SQL queries for matching access records between subjects and targets.
 */
class AccessRecordQueryBuilder {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * The AccessRecordQueryBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $etm
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $efm
   *   The entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $etm, EntityFieldManagerInterface $efm) {
    $this->entityTypeManager = $etm;
    $this->entityFieldManager = $efm;
  }

  /**
   * Get the query builder service.
   *
   * @return \Drupal\access_records\AccessRecordQueryBuilder
   *   The query builder.
   */
  public static function get(): AccessRecordQueryBuilder {
    return \Drupal::service('access_records.query_builder');
  }

  /**
   * Select matching rows by the given type of access records.
   *
   * When the type is properly configured, it returns a select query as object,
   * with the following SELECTed fields:
   *   - 'ar_id': The entity ID of the access record.
   *   - 'subject_id': The entity ID of the subject.
   *   - 'target_id': The entity ID of the target.
   *
   * Important: This query object skips access checks regards data-sets that are
   * being joined together. If you use this query outside the scope of an
   * access check, for example for print out the IDs to a user, you need to
   * take care on your own that the according user is only seeing what is
   * allowed to be seen.
   *
   * @param \Drupal\access_records\AccessRecordTypeInterface $ar_type
   *   The access record type.
   * @param int|null $subject_id
   *   (optional) The entity ID of the subject, so that the query is
   *   pre-filtered only for the subject having this ID. If not set, no filter
   *   is applied regarding a subject.
   * @param string $operation
   *   (optional) The requested operation, for example "view", "update" or
   *   "delete". By default, the query looks out for matching "view" operations.
   * @param array $options
   *   (optional) Further options are available, with the following keys:
   *   - 'join_subjects': Whether an INNER JOIN of existing subject records
   *     should be performed. Default is TRUE. When set to FALSE, no
   *     'subject_id' is available in the list of SELECTed fields.
   *   - 'join_targets': Whether an INNER JOIN of existing target records should
   *     be performed. Default is TRUE. When set to FALSE, no 'target_id' is
   *     available in the list of SELECTed fields.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface|null
   *   The select query as object, or NULL when the type configuration is
   *   incomplete or invalid.
   */
  public function selectByType(AccessRecordTypeInterface $ar_type, ?int $subject_id = NULL, string $operation = 'view', array $options = []) : ?SelectInterface {
    $field_names = [
      'subject' => $ar_type->getSubjectFieldNames(),
      'target' => $ar_type->getTargetFieldNames(),
    ];

    if (empty($field_names['subject']) || empty($field_names['target'])) {
      // To avoid possible security implications due to wrong or incomplete
      // configured access record types, do not build a condition group.
      // This may happen for example when a field was (accidentally) removed.
      return NULL;
    }

    $all_fields = ['ar' => [], 'subject' => [], 'target' => []];

    $ar_entity_type_id = $ar_type->getEntityType()->getBundleOf();
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $types */
    $types = [
      'ar' => $this->entityTypeManager->getDefinition($ar_entity_type_id),
      'subject' => $ar_type->getSubjectType(),
      'target' => $ar_type->getTargetType(),
    ];

    $options += [
      'join_subjects' => TRUE,
      'join_targets' => TRUE,
    ];

    /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface[][] $field_storage_definitions */
    $field_storage_definitions = [
      'subject' => $this->entityFieldManager->getFieldStorageDefinitions($types['subject']->id()),
      'target' => $this->entityFieldManager->getFieldStorageDefinitions($types['target']->id()),
    ];
    foreach ($this->entityFieldManager->getFieldDefinitions($ar_entity_type_id, $ar_type->id()) as $field_name => $ar_field_defintion) {
      $field_names['ar'][$field_name] = $field_name;
      $field_storage_definitions['ar'][$field_name] = $ar_field_defintion->getFieldStorageDefinition();
    }

    /** @var \Drupal\Core\Entity\EntityStorageInterface[] $storages */
    $storages = [];
    /** @var \Drupal\Core\Entity\Query\Sql\Query[] $queries */
    $queries = [];
    // Get all table mapping instances, as we need to check whether a field
    // is stored in a shared table amongst other fields. If so, we won't use
    // the column definitions of the field as it would otherwise break, because
    // entity queries automatically join further tables once column names are
    // explicitly used.
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping[] $table_mappings */
    $table_mappings = [];
    /** @var \Drupal\Core\Entity\Query\ConditionInterface[] $ors */
    $ors = [];
    foreach ($types as $key => $type) {
      $storage = $this->entityTypeManager->getStorage($type->id());
      $query = $storage->getQuery();
      if (!($query instanceof Query)) {
        // As we need to work with a Select object later on, we can only be made
        // sure it's available using the SQL-related Query class.
        return NULL;
      }
      // Access checks are disabled because the condition that is being build
      // here *is* about access checks.
      $query->accessCheck(FALSE);
      $storages[$key] = $storage;
      $queries[$key] = $query;
      $table_mappings[$key] = $storage->getTableMapping();
      $ors[$key] = $query->orConditionGroup();
    }

    // Apply some initial filtering: Only use access records that match up
    // for the given type, operation, are enabled and match between the
    // specified subject type and target type.
    $queries['ar']
      ->condition('ar_type', $ar_type->id())
      ->condition('ar_operation', $operation)
      ->condition('ar_enabled', 1)
      ->condition('ar_subject_type', $types['subject']->id())
      ->condition('ar_target_type', $types['target']->id());

    if (isset($subject_id)) {
      // Only take care for one subject, which is the one with the specified ID.
      $queries['subject']->condition($types['subject']->getKey('id'), $subject_id);
    }

    // Include all fields that we need into the query result. Access records
    // having no values at all regarding subject or target will be ignored.
    $selections = [];
    foreach ($types as $scope => $type) {
      $selections[$scope][] = [
        'table' => $type->getDataTable(),
        'field' => $type->getKey('id'),
        'alias' => $scope . '__' . $type->getKey('id'),
      ];
    }
    foreach (array_keys($field_names['subject'] + $field_names['target']) as $ar_field_name) {
      $fields = $property_names = ['ar' => [], 'subject' => [], 'target' => []];
      $main_properties = [];

      foreach (['ar', 'subject', 'target'] as $scope) {
        if (!isset($field_names[$scope][$ar_field_name])) {
          continue;
        }
        $scope_field_name = $field_names[$scope][$ar_field_name];
        if ($table_mappings[$scope]->requiresDedicatedTableStorage($field_storage_definitions[$scope][$scope_field_name])) {
          $col_names = array_keys($field_storage_definitions[$scope][$scope_field_name]->getColumns());
          $property_names[$scope] = array_combine($col_names, $col_names);
          $main_properties[$scope] = $field_storage_definitions[$scope][$scope_field_name]->getMainPropertyName();
        }
        if (empty($property_names[$scope])) {
          $mapped_ar_field = $ar_field_name;
          if (!empty($main_properties['ar'])) {
            $mapped_ar_field .= '_' . $main_properties['ar'];
          }
          $fields[$scope] = [$mapped_ar_field => $scope_field_name];
          $all_fields[$scope][$mapped_ar_field] = $scope_field_name;
        }
        else {
          foreach ($property_names[$scope] as $property_name) {
            $mapped_ar_field = $ar_field_name;
            if (isset($property_names['ar'][$property_name]) || $scope === 'ar') {
              $mapped_ar_field .= '_' . $property_name;
            }
            elseif ((1 === count($property_names['ar'])) && (1 === count($property_names[$scope]))) {
              $mapped_ar_field .= '_' . reset($property_names['ar']);
            }
            $col_field = $scope_field_name . '_' . $property_name;
            $fields[$scope][$mapped_ar_field] = $col_field;
            $all_fields[$scope][$mapped_ar_field] = $scope_field_name . '.' . $property_name;
          }
        }
        foreach ($fields[$scope] as $mapped_ar_field => $col_field) {
          $scope_table = array_key_exists($scope, $main_properties) ? $types[$scope]->id() . '__' . $scope_field_name : $types[$scope]->getDataTable();
          $selections[$scope][] = [
            'table' => $scope_table,
            'field' => $col_field,
            'alias' => $scope . '__' . $mapped_ar_field,
          ];
        }
      }
    }

    foreach ($all_fields as $scope => $scope_fields) {
      $or_group = $ors[$scope];
      foreach ($scope_fields as $scope_field) {
        $or_group->condition($scope_field, NULL, 'IS NOT NULL');
      }
      $queries[$scope]->condition($or_group);
    }

    // Sorry to do this, but we want to use the convenience to build entity
    // queries, and need the underlying select query as an object, for being
    // able to pass it as an accepted subquery for the condition being built.
    // Therefore, a closure is created that returns the underlying query object.
    /** @var \Drupal\Core\Database\Query\SelectInterface[] $queries */
    $closure = \Closure::fromCallable(function () {
      /** @var \Drupal\Core\Entity\Query\Sql\Query $this */
      $this->prepare()
        ->compile()
        ->addSort()
        ->finish();
      return $this->sqlQuery;
    });
    foreach ($queries as $scope => $query) {
      $queries[$scope] = $closure->call($query);
    }

    foreach ($selections as $scope => $scope_selections) {
      /** @var \Drupal\Core\Database\Query\SelectInterface $query */
      $query = $queries[$scope];
      $q_fields = &$query->getFields();
      $q_fields = [];
      foreach ($scope_selections as $selection) {
        $query->addField($selection['table'], $selection['field'], $selection['alias']);
        $query->orderBy($selection['alias']);
        $query->groupBy($selection['alias']);
      }
    }

    $id_query = \Drupal::database()->select($queries['ar'], 'ar');
    $id_query->addField('ar', 'ar__ar_id', 'ar_id');
    $id_query->orderBy('ar_id');
    $id_query->groupBy('ar_id');
    foreach (['subject', 'target'] as $scope) {
      if (empty($options["join_${scope}s"])) {
        continue;
      }

      $join_conditions = [];
      foreach (array_keys($all_fields[$scope]) as $mapped_ar_field) {
        $join_conditions[] = "[${scope}].[${scope}__${mapped_ar_field}] = [ar].[ar__${mapped_ar_field}]";
      }
      // Extra care is taken for users as subjects (that is mostly the case):
      // Add a check for the user ID. If that one is 0, then it's anonymous
      // and otherwise it's an authenticated user. The according user role
      // is not stored in the database though, that's why we add it here.
      if (($scope === 'subject') && ($types[$scope]->id() === 'user') && ($mapped_ar_field = array_search('roles.target_id', $all_fields[$scope]))) {
        $join_conditions[] = "([ar].[ar__${mapped_ar_field}] = '" . AccountInterface::ANONYMOUS_ROLE . "' AND [subject].[subject__uid] = 0)";
        $join_conditions[] = "([ar].[ar__${mapped_ar_field}] = '" . AccountInterface::AUTHENTICATED_ROLE . "' AND [subject].[subject__uid] <> 0)";
      }
      $join_conditions = implode(' OR ', $join_conditions);

      $id_query->join($queries[$scope], $scope, $join_conditions);
      $scope_alias = $scope . '_id';
      $id_query->addField($scope, $scope . '__' . $types[$scope]->getKey('id'), $scope_alias);
      $id_query->orderBy($scope_alias);
      $id_query->groupBy($scope_alias);
    }

    return $id_query;
  }

}
