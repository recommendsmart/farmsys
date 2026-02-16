<?php

namespace Drupal\entity_taxonomy;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the entity_taxonomy entity type.
 */
class TermViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['entity_taxonomy_term_field_data']['table']['base']['help'] = $this->t('entity_taxonomy terms are attached to nodes.');
    $data['entity_taxonomy_term_field_data']['table']['base']['access query tag'] = 'entity_taxonomy_term_access';
    $data['entity_taxonomy_term_field_data']['table']['wizard_id'] = 'entity_taxonomy_term';

    $data['entity_taxonomy_term_field_data']['table']['join'] = [
      // This is provided for the many_to_one argument.
      'entity_taxonomy_index' => [
        'field' => 'tid',
        'left_field' => 'tid',
      ],
    ];

    $data['entity_taxonomy_term_field_data']['tid']['help'] = $this->t('The tid of a entity_taxonomy term.');

    $data['entity_taxonomy_term_field_data']['tid']['argument']['id'] = 'entity_taxonomy';
    $data['entity_taxonomy_term_field_data']['tid']['argument']['name field'] = 'name';
    $data['entity_taxonomy_term_field_data']['tid']['argument']['zero is null'] = TRUE;

    $data['entity_taxonomy_term_field_data']['tid']['filter']['id'] = 'entity_taxonomy_index_tid';
    $data['entity_taxonomy_term_field_data']['tid']['filter']['title'] = $this->t('Term');
    $data['entity_taxonomy_term_field_data']['tid']['filter']['help'] = $this->t('entity_taxonomy term chosen from autocomplete or select widget.');
    $data['entity_taxonomy_term_field_data']['tid']['filter']['hierarchy table'] = 'entity_taxonomy_term__parent';
    $data['entity_taxonomy_term_field_data']['tid']['filter']['numeric'] = TRUE;

    $data['entity_taxonomy_term_field_data']['tid_raw'] = [
      'title' => $this->t('Term ID'),
      'help' => $this->t('The tid of a entity_taxonomy term.'),
      'real field' => 'tid',
      'filter' => [
        'id' => 'numeric',
        'allow empty' => TRUE,
      ],
    ];

    $data['entity_taxonomy_index']['table']['join'] = [
      'entity_taxonomy_term_field_data' => [
        // links directly to entity_taxonomy_term_field_data via tid
        'left_field' => 'tid',
        'field' => 'tid',
      ],
      'node_field_data' => [
        // links directly to node via nid
        'left_field' => 'nid',
        'field' => 'entity_id',
      ],
      'entity_taxonomy_term__parent' => [
        'left_field' => 'entity_id',
        'field' => 'tid',
      ],
    ];

    $entity_list_def = \Drupal::entityTypeManager()->getDefinitions();
    $entity_list = array_keys($entity_list_def);
    unset($entity_list_def['node']);  
    $not_categorized = [
      'entity_taxonomy',
      'taxonomy',
    ];
    $ti_table=!\Drupal::config('entity_taxonomy.settings')->get('maintain_index_table');
    // only data for current, published nodes.
    foreach ($entity_list_def as $entity_type_id => $entity_def) {
      if ($ti_table || !(\Drupal::entityTypeManager()->getStorage($entity_type_id) instanceof SqlContentEntityStorage) || in_array($entity_type_id, $not_categorized)) {
        continue;
      }
      $base_table = $entity_def->get('base_table');
      $data_table = $entity_def->get('data_table');
      $entity_keys = $entity_def->get('entity_keys');

      $data['entity_taxonomy_index']['table']['join'][$data_table] = [
          'left_field' => $entity_keys['id'],
          'field' => 'entity_id',
      ];
      $data['entity_taxonomy_term_field_data'][$entity_type_id.'_tid_representative'] = [
        'relationship' => [
          'title' => $this->t('Representative '.$entity_type_id),
          'label'  => $this->t('Representative '.$entity_type_id),
          'help' => $this->t('Obtains a single representative node for each term, according to a chosen sort criterion.'),
          'id' => 'groupwise_max',
          'relationship field' => 'tid',
          'outer field' => 'entity_taxonomy_term_field_data.tid',
          'argument table' => 'entity_taxonomy_term_field_data',
          'argument field' => 'tid',
          'base'   => $data_table,
          'field'  => $entity_keys['id'],
          'relationship' => $data_table.':term_node_tid',
        ],
      ];
    }

    $data['entity_taxonomy_term_field_data']['tid_representative'] = [
      'relationship' => [
        'title' => $this->t('Representative node'),
        'label'  => $this->t('Representative node'),
        'help' => $this->t('Obtains a single representative node for each term, according to a chosen sort criterion.'),
        'id' => 'groupwise_max',
        'relationship field' => 'tid',
        'outer field' => 'entity_taxonomy_term_field_data.tid',
        'argument table' => 'entity_taxonomy_term_field_data',
        'argument field' => 'tid',
        'base'   => 'node_field_data',
        'field'  => 'nid',
        'relationship' => 'node_field_data:term_node_tid',
      ],
    ];

    $data['entity_taxonomy_term_field_data']['vid']['help'] = $this->t('Filter the results of "entity_taxonomy: Term" to a particular vocabulary.');
    $data['entity_taxonomy_term_field_data']['vid']['field']['help'] = t('The vocabulary name.');
    $data['entity_taxonomy_term_field_data']['vid']['argument']['id'] = 'vocabulary_vid';
    unset($data['entity_taxonomy_term_field_data']['vid']['sort']);

    $data['entity_taxonomy_term_field_data']['name']['field']['id'] = 'term_name';
    $data['entity_taxonomy_term_field_data']['name']['argument']['many to one'] = TRUE;
    $data['entity_taxonomy_term_field_data']['name']['argument']['empty field name'] = $this->t('Uncategorized');

    $data['entity_taxonomy_term_field_data']['description__value']['field']['click sortable'] = FALSE;

    $data['entity_taxonomy_term_field_data']['changed']['title'] = $this->t('Updated date');
    $data['entity_taxonomy_term_field_data']['changed']['help'] = $this->t('The date the term was last updated.');

    $data['entity_taxonomy_term_field_data']['changed_fulldate'] = [
      'title' => $this->t('Updated date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_fulldate',
      ],
    ];

    $data['entity_taxonomy_term_field_data']['changed_year_month'] = [
      'title' => $this->t('Updated year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_year_month',
      ],
    ];

    $data['entity_taxonomy_term_field_data']['changed_year'] = [
      'title' => $this->t('Updated year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_year',
      ],
    ];

    $data['entity_taxonomy_term_field_data']['changed_month'] = [
      'title' => $this->t('Updated month'),
      'help' => $this->t('Date in the form of MM (01 - 12).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_month',
      ],
    ];

    $data['entity_taxonomy_term_field_data']['changed_day'] = [
      'title' => $this->t('Updated day'),
      'help' => $this->t('Date in the form of DD (01 - 31).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_day',
      ],
    ];

    $data['entity_taxonomy_term_field_data']['changed_week'] = [
      'title' => $this->t('Updated week'),
      'help' => $this->t('Date in the form of WW (01 - 53).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_week',
      ],
    ];

    $data['entity_taxonomy_index']['table']['group'] = $this->t('entity_taxonomy term');

    $data['entity_taxonomy_index']['entity_id'] = [
      'title' => $this->t('Content with term'),
      'help' => $this->t('Relate all content tagged with a term.'),
      'relationship' => [
        'id' => 'standard',
        'base' => 'node_field_data',
        'base field' => 'nid',
        'label' => $this->t('node'),
        'skip base' => 'node_field_data',
      ],
    ];

    // Link to self through left.parent = right.tid (going down in depth).
    $data['entity_taxonomy_term__parent']['table']['join']['entity_taxonomy_term__parent'] = [
      'left_field' => 'entity_id',
      'field' => 'parent_target_id',
    ];

    $data['entity_taxonomy_term__parent']['parent_target_id']['help'] = $this->t('The parent term of the term. This can produce duplicate entries if you are using a vocabulary that allows multiple parents.');
    $data['entity_taxonomy_term__parent']['parent_target_id']['relationship']['label'] = $this->t('Parent');
    $data['entity_taxonomy_term__parent']['parent_target_id']['argument']['id'] = 'entity_taxonomy';

    return $data;
  }

}
