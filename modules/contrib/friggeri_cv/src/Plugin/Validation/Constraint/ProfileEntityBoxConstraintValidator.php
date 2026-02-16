<?php

namespace Drupal\friggeri_cv\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Profile Entity Box constraint.
 */
class ProfileEntityBoxConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($item, Constraint $constraint) {
    $value = $item->getValue();
    $title = $value['title'];
    $employer = $value['employer'];
    $info = $value['info'];
    if (strlen($info) > 255) {
      $this->context->addViolation($constraint->errorMessage);
    }

    if (strlen($title) + strlen($employer) > 63) {
      $this->context->addViolation($constraint->overlapMessage);
    }

  }

}
