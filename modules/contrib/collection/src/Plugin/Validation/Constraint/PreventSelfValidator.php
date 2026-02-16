<?php

namespace Drupal\collection\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the PreventSelf constraint.
 */
class PreventSelfValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($collection_item, Constraint $constraint) {
    if ($collection_item->collection->entity === $collection_item->item->entity) {
      $this->context->addViolation($constraint->inception, [
        '%entity' => $collection_item->item->entity->label(),
      ]);
    }
  }

}
