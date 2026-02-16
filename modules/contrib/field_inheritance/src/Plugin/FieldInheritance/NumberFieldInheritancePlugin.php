<?php

namespace Drupal\field_inheritance\Plugin\FieldInheritance;

use Drupal\field_inheritance\FieldInheritancePluginInterface;

/**
 * Number Inheritance plugin.
 *
 * @FieldInheritance(
 *   id = "number_inheritance",
 *   name = @Translation("Number Field Inheritance"),
 *   types = {
 *     "decimal",
 *     "float",
 *     "integer",
 *     "list_float",
 *     "list_integer"
 *   }
 * )
 */
class NumberFieldInheritancePlugin extends FieldInheritancePluginBase implements FieldInheritancePluginInterface {
}
