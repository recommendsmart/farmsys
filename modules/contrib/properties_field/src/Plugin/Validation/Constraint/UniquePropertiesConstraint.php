<?php

namespace Drupal\properties_field\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if the properies are unique.
 *
 * @Constraint(
 *   id = "UniqueProperties",
 *   label = @Translation("Properies unique", context = "Validation")
 * )
 */
class UniquePropertiesConstraint extends Constraint {

  /**
   * Message shown when the label isn't unique.
   *
   * @var string
   */
  public $labelExistsMessage = 'Label @label is not unique.';

  /**
   * Message shown when the machine name isn't unique.
   *
   * @var string
   */
  public $machineNameExistsMessage = 'Machine name @machine_name is not unique.';

}
