<?php

/**
 * @file
 * Post-update functions for the Entity Extra Field module.
 */

/**
 * Clear caches to ensure split with entity_extra_field_ui is effective.
 */
function entity_extra_field_post_update_rebuild_cache_after_ui_split() {
  return t('Entity Extra Field UI has been moved to a new submodule. To enable 
    the management UI back install Entity Extra Field UI module.');
}
