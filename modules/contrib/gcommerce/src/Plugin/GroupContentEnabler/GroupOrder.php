<?php

namespace Drupal\gcommerce\Plugin\GroupContentEnabler;

use Drupal\group\Plugin\GroupContentEnablerBase;

/**
 * Allows Commerce order to be added to groups.
 *
 * @GroupContentEnabler(
 *   id = "group_order",
 *   label = @Translation("Group commerce order"),
 *   description = @Translation("Adds commerce order to groups."),
 *   entity_type_id = "commerce_order",
 *   entity_access = TRUE,
 *   reference_label = @Translation("Title"),
 *   reference_description = @Translation("The title of the commerce order to add to the group"),
 *   deriver = "Drupal\gcommerce\Plugin\GroupContentEnabler\GroupOrderDeriver"
 * )
 */
class GroupOrder extends GroupContentEnablerBase {

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $dependencies['config'][] = 'commerce_order.commerce_order_type.' . $this->getEntityBundle();
    return $dependencies;
  }
}
