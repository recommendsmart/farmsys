<?php

namespace Drupal\commerceg_context\MachineName\Field;

/**
 * Holds machine names of User entity fields.
 *
 * See https://github.com/krystalcode/drupal8-coding-standards/blob/master/Fields.md#field-name-constants
 */
class User {

  /**
   * Holds the default context for the user.
   */
  const DEFAULT_CONTEXT = 'commerceg_context_default';

  /**
   * Holds whether to remember the last used context next time the user logs in.
   */
  const REMEMBER_CONTEXT = 'commerceg_context_remember';

}
