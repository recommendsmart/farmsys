<?php

namespace Drupal\commerce_funds\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * User funds validation constraint.
 *
 * @Constraint(
 *  id = "NetAmountBelowBalance",
 *  label = @Translation("Net amount is superior to balance amount.", context="Validation")
 * )
 */
class NetAmountBelowBalanceConstraint extends Constraint {
  /**
   * {@inheritdoc}
   */
  public $message = "You don't have enough funds to cover this transfer.";
  /**
   * {@inheritdoc}
   */
  public $messageWithFee = "You don't have enough funds to cover this transfer.<br>
   The commission applied is %commission (@currency).";

}
