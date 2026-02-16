<?php

namespace Drupal\commerceg\MachineName\Config;

/**
 * Holds machine names related to the disabled mode of personal context.
 *
 * See https://github.com/krystalcode/drupal8-coding-standards/blob/master/Fields.md#field-name-constants
 */
class PersonalContextDisabledMode {

  /**
   * Holds the machine name for the Disable disabled mode.
   *
   * The Disable disabled mode instructs any responsible software components to
   * disable any UI elements that would allow users to create carts e.g. disable
   * the "Add to cart" form submit button.
   */
  const MODE_DISABLE = 'disable';

  /**
   * Holds the machine name for the Hide disabled mode.
   *
   * The Hide disabled mode instructs any responsible software components to
   * hide any UI elements that would allow users to create carts e.g. hide
   * the "Add to cart" form submit button.
   */
  const MODE_HIDE = 'hide';

}
