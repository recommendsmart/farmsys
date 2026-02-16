<?php

namespace Drupal\collection\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Prevents a collection from being added to itself.
 *
 * @Constraint(
 *   id = "PreventSelf",
 *   label = @Translation("Single Canonical Item", context = "Validation"),
 *   type = "string"
 * )
 */
class PreventSelf extends Constraint {

  public $inception = '%entity cannot be added to itself.';

}
