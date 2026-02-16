<?php

namespace Drupal\field_inheritance\Plugin\FieldInheritance;

use Drupal\field_inheritance\FieldInheritancePluginInterface;

/**
 * Boolean Inheritance plugin.
 *
 * @FieldInheritance(
 *   id = "boolean_inheritance",
 *   name = @Translation("Boolean Field Inheritance"),
 *   types = {
 *     "boolean"
 *   }
 * )
 */
class BooleanFieldInheritancePlugin extends FieldInheritancePluginBase implements FieldInheritancePluginInterface {
}
