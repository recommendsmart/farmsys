<?php

namespace Drupal\commerceg\MachineName\Plugin;

/**
 * Holds machine names of Group Content Enabler plugins.
 *
 * See https://github.com/krystalcode/drupal8-coding-standards/blob/master/Fields.md#field-name-constants
 */
class GroupContentEnabler {

  /**
   * Holds the machine name for the Group Membership enabler plugin.
   *
   * The plugin is provided by the Group module and is used across all group
   * types to add users as members in groups.
   */
  const MEMBERSHIP = 'group_membership';

  /**
   * Holds the machine name for the Order enabler plugin.
   *
   * Since the group content enabler plugin provides derivatives per order type,
   * the final ID for each plugin will be `commerceg_order:XXX` where `XXX` is
   * the machine name of the order type. We can only provide the base plugin ID
   * here.
   */
  const ORDER = 'commerceg_order';

  /**
   * Holds the machine name for the Product enabler plugin.
   *
   * Since the group content enabler plugin provides derivatives per product
   * type, the final ID for each plugin will be `commerceg_product:XXX` where
   * `XXX` is the machine name of the product type. We can only provide the base
   * plugin ID here.
   */
  const PRODUCT = 'commerceg_product';

  /**
   * Holds the machine name for the Profile enabler plugin.
   *
   * Since the group content enabler plugin provides derivatives per profile
   * type, the final ID for each plugin will be `commerceg_profile:XXX` where
   * `XXX` is the machine name of the profile type. We can only provide the base
   * plugin ID here.
   */
  const PROFILE = 'commerceg_profile';

}
