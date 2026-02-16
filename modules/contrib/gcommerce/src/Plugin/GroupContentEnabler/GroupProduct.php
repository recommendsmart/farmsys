<?php

namespace Drupal\gcommerce\Plugin\GroupContentEnabler;

use Drupal\group\Plugin\GroupContentEnablerBase;

/**
 * Provides a content enabler for products.
 *
 * @GroupContentEnabler(
 *   id = "group_product",
 *   label = @Translation("Group commerce product"),
 *   description = @Translation("Adds commerce product to groups."),
 *   entity_type_id = "commerce_product",
 *   entity_access = TRUE,
 *   reference_label = @Translation("Title"),
 *   reference_description = @Translation("The title of the commerce product to add to the group"),
 *   deriver = "Drupal\gcommerce\Plugin\GroupContentEnabler\GroupProductDeriver"
 * )
 */
class GroupProduct extends GroupContentEnablerBase {
}
