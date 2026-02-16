<?php

namespace Drupal\gcommerce\Plugin\GroupContentEnabler;

use Drupal\group\Plugin\GroupContentEnablerBase;

/**
 * Provides a content enabler for stores.
 *
 * @GroupContentEnabler(
 *   id = "group_store",
 *   label = @Translation("Group commerce store"),
 *   description = @Translation("Adds commerce store to groups."),
 *   entity_type_id = "commerce_store",
 *   entity_access = TRUE,
 *   reference_label = @Translation("Title"),
 *   reference_description = @Translation("The title of the commerce store to add to the group"),
 *   deriver = "Drupal\gcommerce\Plugin\GroupContentEnabler\GroupStoreDeriver"
 * )
 */
class GroupStore extends GroupContentEnablerBase {
}
