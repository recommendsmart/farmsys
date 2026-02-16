<?php

namespace Drupal\commerce_transaction\MachineName\Field;

/**
 * Holds machine names of Transaction entity fields.
 *
 * @link https://github.com/krystalcode/drupal8-coding-standards/blob/master/Fields.md#field-name-constants
 */
class Transaction {

  /**
   * Holds the machine name for the ID field.
   */
  const ID = 'id';

  /**
   * Holds the machine name for the UUID field.
   */
  const UUID = 'uuid';

  /**
   * Holds the machine name for the type field (bundle).
   */
  const TYPE = 'type';

  /**
   * Holds the machine name for the payment ID field.
   */
  const PAYMENT_ID = 'payment_id';

  /**
   * Holds the machine name for the amount field.
   */
  const AMOUNT = 'amount';

  /**
   * Holds the machine name for the remote ID field.
   */
  const REMOTE_ID = 'remote_id';

  /**
   * Holds the machine name for the remote state field.
   *
   * Unlike the remote state field in the `commerce_payment` entity, the
   * transaction remote state field is implemented as a state machine state
   * field. State transitions are triggered when we fetch transaction updates
   * from the remote system and event subscribers can update the parent payments
   * as needed.
   */
  const REMOTE_STATE = 'remote_state';

}
