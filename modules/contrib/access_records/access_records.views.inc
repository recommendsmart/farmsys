<?php

/**
 * @file
 * Provides views data for access records.
 */

/**
 * Implements hook_views_data_alter().
 */
function access_records_views_data_alter(&$data) {
  $etm = \Drupal::entityTypeManager();
  $type_storage = $etm->getStorage('access_record_type');
  $ar_entity_type = $etm->getDefinition('access_record');
  $ar_data_table = $ar_entity_type->getDataTable();

  $data[$ar_data_table]['ar_subject_id'] = [
    'title' => t('Subject ID'),
    'help' => t('The entity ID of the according subject. Will produce duplicate rows when multiple subjects match to one access record.'),
    'field' => [
      'title' => t('Subject ID'),
      'help' => t('The entity ID of the according subject. Will produce duplicate rows when multiple subjects match to one access record.'),
      'id' => 'access_record_subject_id',
      'no group by' => TRUE,
      'click sortable' => FALSE,
    ],
  ];
  $data[$ar_data_table]['ar_target_id'] = [
    'title' => t('Target ID'),
    'help' => t('The entity ID of the according target. Will produce duplicate rows when multiple subjects match to one access record.'),
    'field' => [
      'title' => t('Target ID'),
      'help' => t('The entity ID of the according target. Will produce duplicate rows when multiple subjects match to one access record.'),
      'id' => 'access_record_target_id',
      'no group by' => TRUE,
      'click sortable' => FALSE,
    ],
  ];

  $entity_types_included = [];
  /** @var \Drupal\access_records\AccessRecordTypeInterface $type */
  foreach ($type_storage->loadMultiple() as $type) {
    if (!$type->status()) {
      continue;
    }

    $candidates = [];
    if (!isset($entity_types_included[$type->getSubjectTypeId()])) {
      $subject_type_id = $type->getSubjectTypeId();
      $subject_type = $type->getSubjectType();
      $candidates[$subject_type_id] = $subject_type->getDataTable();
      $entity_types_included[$subject_type_id] = TRUE;

      $data[$ar_data_table][$subject_type_id . '_subjects'] = [
        'title' => t('@type subjects', ['@type' => $subject_type->getLabel()]),
        'help' => t('Relate @type rows that match up as subjects from access records.', ['@type' => $subject_type->getLabel()]),
        'relationship' => [
          'id' => 'access_record_subject',
          'label' => t('@type subject', ['@type' => $subject_type->getLabel()]),
          'base' => $subject_type->getDataTable(),
          'subject_type' => $subject_type_id,
        ],
      ];
    }
    if (!isset($entity_types_included[$type->getTargetTypeId()])) {
      $target_type_id = $type->getTargetTypeId();
      $target_type = $type->getTargetType();
      $candidates[$target_type_id] = $target_type->getDataTable();
      $entity_types_included[$target_type_id] = TRUE;

      $data[$ar_data_table][$target_type_id . '_targets'] = [
        'title' => t('@type targets', ['@type' => $target_type->getLabel()]),
        'help' => t('Relate @type rows that match up as targets from access records.', ['@type' => $target_type->getLabel()]),
        'relationship' => [
          'id' => 'access_record_target',
          'label' => t('@type target', ['@type' => $target_type->getLabel()]),
          'base' => $target_type->getDataTable(),
          'target_type' => $target_type_id,
        ],
      ];
    }
    foreach ($candidates as $candidate => $data_table) {
      if (!isset($data[$data_table])) {
        continue;
      }
      $entity_type = $etm->getDefinition($candidate);
      $data[$data_table][$candidate . '_access_records'] = [
        'title' => t('Access records'),
        'help' => t('Relate access records matching with @type rows either as subjects or targets.', ['@type' => $entity_type->getLabel()]),
        'relationship' => [
          'id' => 'access_record_data',
          'label' => t('Access record'),
          'base' => $ar_entity_type->getDataTable(),
          'entity_type' => $candidate,
        ],
      ];
    }
  }
}
