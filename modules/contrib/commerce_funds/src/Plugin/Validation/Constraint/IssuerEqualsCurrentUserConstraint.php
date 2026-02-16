<?php

namespace Drupal\commerce_funds\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Issuer equals current user constraint.
 *
 * @Constraint(
 *  id = "IssuerEqualsCurrentUser",
 *  label = @Translation("Issuer equals current user.", context="Validation")
 * )
 */
class IssuerEqualsCurrentUserConstraint extends Constraint {
  /**
   * {@inheritdoc}
   */
  public $message = "Operation impossible. You can't transfer money to yourself.";

}
