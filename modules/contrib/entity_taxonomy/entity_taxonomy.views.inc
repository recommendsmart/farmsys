<?php

/**
 * @file
 * Provides views data for entity_taxonomy.module.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_views_data_alter().
 */
function entity_taxonomy_views_data_alter(&$data) {
  $entity_list_def = \Drupal::entityTypeManager()->getDefinitions();
  $entity_list = array_keys($entity_list_def);
  $not_categorized = [
    'entity_taxonomy',
    'taxonomy',
    'block_content_type',
    'comment_type',
    'editor',
    'entity_taxonomy_vocabulary',
    'field_config',
    'field_storage_config',
    'filter_format',
    'image_style',
    'menu_link_content',
    'node_type',
    'path_alias',
    'rdf_mapping',
    'search_page',
    'shortcut_set',
    'shortcut',
    'menu',
    'action',
    'tour',
    'user_role',
    'view',
    'entity_view_mode',
    'entity_view_display',
    'entity_form_mode',
    'entity_form_display',
    'base_field_override',
    'date_format',
  ];
  $ti_table=\Drupal::config('entity_taxonomy.settings')->get('maintain_index_table');

  // only data for current, published nodes.
  foreach ($entity_list_def as $entity_type_id => $entity_def) {
    if (in_array($entity_type_id, $not_categorized)) {
      unset($entity_list_def[$entity_type_id]);
      continue;
    }
    $base_table = $entity_def->get('base_table');
    $data_table = $entity_def->get('data_table');
    $entity_keys = $entity_def->get('entity_keys');
    $data[$data_table]['term_'.$entity_type_id.'_tid'] = [
      'title' => t('entity_taxonomy terms on '.$entity_type_id),
      'help' => t('Relate '.$entity_type_id.' to entity_taxonomy terms, specifying which vocabulary or vocabularies to use. This relationship will cause duplicated records if there are multiple terms.'),
      'relationship' => [
        'id' => 'entity_term_data',
        'label' => t('term'),
        'base' => 'entity_taxonomy_term_field_data',
      ],
      'entity_type_id' => $entity_type_id,
      'field' => [
        'title' => t('All entity_taxonomy terms'),
        'help' => t('Display all entity_taxonomy terms associated with a '.$entity_type_id.' from specified vocabularies.'),
        'id' => 'entity_taxonomy_index_tid',
        'no group by' => TRUE,
        'click sortable' => FALSE,
      ],
    ];

    $data[$data_table]['term_'.$entity_type_id.'_tid_depth'] = [
      'help' => t('Display content if it has the selected entity_taxonomy terms, or children of the selected terms. Due to additional complexity, this has fewer options than the versions without depth.'),
      'real field' => $entity_keys['id'],
      'argument' => [
        'title' => t('Has entity_taxonomy term ID (with depth)'),
        'id' => 'entity_taxonomy_index_tid_depth',
        'accept depth modifier' => TRUE,
      ],
      'filter' => [
        'title' => t('Has entity_taxonomy terms (with depth)'),
        'id' => 'entity_taxonomy_index_tid_depth',
      ],
    ];

    $data[$data_table]['term_'.$entity_type_id.'_tid_depth_modifier'] = [
      'title' => t('Has entity_taxonomy term ID depth modifier'),
      'help' => t('Allows the "depth" for entity_taxonomy: Term ID (with depth) to be modified via an additional contextual filter value.'),
      'argument' => [
        'id' => 'entity_taxonomy_index_tid_depth_modifier',
      ],
    ];

      $data['entity_taxonomy_index']['table']['join'][$data_table] = [
          'left_field' => $entity_keys['id'],
          'field' => 'entity_id',
          'extra' => [
            [
              'field' => 'entity_type_id',
              'value' => $entity_type_id,
              'operator' => '=',
            ],
          ],
      ];
      $data['entity_taxonomy_term_field_data'][$entity_type_id.'_tid_representative'] = [
        'relationship' => [
          'title' => t('Representative '.$entity_type_id),
          'label'  => t('Representative '.$entity_type_id),
          'help' => t('Obtains a single representative node for each term, according to a chosen sort criterion.'),
          'id' => 'groupwise_max',
          'relationship field' => 'tid',
          'outer field' => 'entity_taxonomy_term_field_data.tid',
          'argument table' => 'entity_taxonomy_term_field_data',
          'argument field' => 'tid',
          'base'   => $data_table,
          'field'  => $entity_keys['id'],
          'relationship' => $data_table.':term_'.$entity_type_id.'_tid',
        ],
      ];
  }

  $data['entity_taxonomy_index']['tid'] = [
    'group' => t('Global'),
    'title' => t('Has entity_taxonomy term ID'),
    'help' => t('Display entity if it has the selected entity_taxonomy terms.'),
    'argument' => [
      'id' => 'entity_taxonomy_index_tid',
      'name table' => 'entity_taxonomy_term_field_data',
      'name field' => 'name',
      'empty field name' => t('Uncategorized'),
      'numeric' => TRUE,
      'skip base' => 'entity_taxonomy_term_field_data',
    ],
    'filter' => [
      'title' => t('Has entity_taxonomy term'),
      'id' => 'entity_taxonomy_index_tid',
      'hierarchy table' => 'entity_taxonomy_term__parent',
      'numeric' => TRUE,
      'skip base' => 'entity_taxonomy_term_field_data',
      'allow empty' => TRUE,
    ],
  ];

}

/**
 * Implements hook_field_views_data_alter().
 *
 * Views integration for entity reference fields which reference entity_taxonomy terms.
 * Adds a term relationship to the default field data.
 *
 * @see views_field_default_views_data()
 */
function entity_taxonomy_field_views_data_alter(array &$data, FieldStorageConfigInterface $field_storage) {
  if ($field_storage->getType() == 'entity_reference' && $field_storage->getSetting('target_type') == 'entity_taxonomy_term') {
    foreach ($data as $table_name => $table_data) {
      foreach ($table_data as $field_name => $field_data) {
        if (isset($field_data['filter']) && $field_name != 'delta') {
          $data[$table_name][$field_name]['filter']['id'] = 'entity_taxonomy_index_tid';
        }
      }
    }
  }
}

/**
 * Implements hook_views_plugins_argument_validator_alter().
 *
 * Extend the generic entity argument validator.
 *
 * @see \Drupal\views\Plugin\views\argument_validator\Entity
 */
function entity_taxonomy_views_plugins_argument_validator_alter(array &$plugins) {
  $plugins['entity:entity_taxonomy_term']['title'] = t('entity_taxonomy term ID');
  $plugins['entity:entity_taxonomy_term']['class'] = 'Drupal\entity_taxonomy\Plugin\views\argument_validator\Term';
  $plugins['entity:entity_taxonomy_term']['provider'] = 'entity_taxonomy';
}
