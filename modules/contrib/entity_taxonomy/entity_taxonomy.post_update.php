<?php

/**
 * @file
 * Post update functions for entity_taxonomy.
 */

/**
 * Implements hook_removed_post_updates().
 */
function entity_taxonomy_removed_post_updates() {
  return [
    'entity_taxonomy_post_update_clear_views_data_cache' => '9.0.0',
    'entity_taxonomy_post_update_clear_entity_bundle_field_definitions_cache' => '9.0.0',
    'entity_taxonomy_post_update_handle_publishing_status_addition_in_views' => '9.0.0',
    'entity_taxonomy_post_update_remove_hierarchy_from_vocabularies' => '9.0.0',
    'entity_taxonomy_post_update_make_entity_taxonomy_term_revisionable' => '9.0.0',
    'entity_taxonomy_post_update_configure_status_field_widget' => '9.0.0',
  ];
}
