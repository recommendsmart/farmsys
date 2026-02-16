<?php

namespace Drupal\friggeri_cv\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a Profile Entity Box constraint.
 *
 * @Constraint(
 *   id = "friggeri_cv_profile_entity_box_constraint",
 *   label = @Translation("Profile Entity Box", context = "Validation"),
 * )
 */
class ProfileEntityBoxConstraint extends Constraint {

  /**
   * Error message for character limit.
   *
   * @var string
   */
  public $errorMessage = 'The info exceeds 255 characters.';

  /**
   * Error message for the overlap of the textfields.
   *
   * @var string
   */
  public $overlapMessage = 'Title text overlaps with employer text';

}
