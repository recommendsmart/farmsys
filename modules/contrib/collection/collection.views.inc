<?php

/**
 * Implements hook_views_data().
 *
 * Add fields to the view that mirror the CollectionItemListBuilder.
 * Add a relationship from nodes to canonical collection items.
 */
function collection_views_data() {
  $data['collection_item']['entity_type_label'] = [
    'title' => t('Collected item type'),
    'field' => [
      'id' => 'collection_item_collected_item_entity_type_label',
    ],
  ];

  $data['collection_item']['status'] = [
    'title' => t('Item status'),
    'field' => [
      'id' => 'collection_item_collected_item_status',
    ],
  ];

  $data['collection_item']['state'] = [
    'title' => t('Item moderation state'),
    'field' => [
      'id' => 'collection_item_collected_item_state',
    ],
    'help' => t('Requires the Content Moderation module'),
  ];

  // This relationship is special because collection items use dynamic entity
  // references and therefore the joins need to be more specific.
  // @todo Consider refactoring this to any content entity that can be in a
  //   collection. @see comment.views.inc.
  $data['node']['canonical_collection_item'] = [
    'title' => t('Canonical collection item'),
    'help' => t('The canonical collection item for this content'),
    'relationship' => [
      'group' => t('Collection'),
      'label' => t('Canonical Collection Item'),
      'id' => 'standard',
      'base' => 'collection_item_field_data',
      'base field' => 'item__target_id_int',
      'relationship field' => 'nid',
      'extra' => [
        [
          'field' => 'item__target_type',
          'value' => 'node',
        ],
        [
          'field' => 'canonical',
          'value' => 1,
        ],
      ],
    ],
  ];

  return $data;
}
