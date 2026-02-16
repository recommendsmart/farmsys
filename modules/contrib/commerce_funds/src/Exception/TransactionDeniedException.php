<?php

namespace Drupal\commerce_funds\Exception;

use Drupal\Core\Session\AccountProxy;

/**
 * Thrown when trying to perform a transaction without the permission.
 */
class TransactionDeniedException extends \Exception {}
