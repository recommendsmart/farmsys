<?php

namespace Drupal\commerce_funds\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the IssuerEqualsCurrentUser Constraint.
 *
 * @package Drupal\commerce_funds\Plugin\Validation\Constraint
 */
class IssuerEqualsCurrentUserConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    $current_user = \Drupal::currentUser();
    $recipient_id = $items->getValue()[0]['target_id'];
    // Error if user try to make a transaction to itself.
    if ($current_user->id() == $recipient_id) {
      $this->context->addViolation($constraint->message);
    }
  }

}
