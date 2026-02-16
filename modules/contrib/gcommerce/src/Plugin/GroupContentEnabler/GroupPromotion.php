<?php

namespace Drupal\gcommerce\Plugin\GroupContentEnabler;

use Drupal\group\Plugin\GroupContentEnablerBase;

/**
 * Provides a content enabler for promotions.
 *
 * @GroupContentEnabler(
 *   id = "group_promotion",
 *   label = @Translation("Group promotion"),
 *   description = @Translation("Adds promotion to groups both publicly and privately."),
 *   entity_type_id = "commerce_promotion",
 *   entity_access = TRUE,
 *   reference_label = @Translation("Title"),
 *   reference_description = @Translation("The title of the promotion to add to the group"),
 * )
 */
class GroupPromotion extends GroupContentEnablerBase {

  /**
   * {@inheritdoc}
   */
  protected function getTargetEntityPermissions() {
    $plugin_id = $this->getPluginId();
    $permissions = parent::getTargetEntityPermissions();
    // Commerce promotion don't have owner, so delete these permission until
    // https://www.drupal.org/project/commerce/issues/2965729 be solved on
    // commerce module.
    unset($permissions["update own $plugin_id entity"]);
    unset($permissions["delete own $plugin_id entity"]);
    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  protected function getGroupContentPermissions() {
    $plugin_id = $this->getPluginId();
    $permissions = parent::getGroupContentPermissions();
    // Commerce promotion don't have owner, so delete these permission until
    // https://www.drupal.org/project/commerce/issues/2965729 be solved on
    // commerce module.
    unset($permissions["update own $plugin_id content"]);
    unset($permissions["delete own $plugin_id content"]);
    return $permissions;
  }

}
