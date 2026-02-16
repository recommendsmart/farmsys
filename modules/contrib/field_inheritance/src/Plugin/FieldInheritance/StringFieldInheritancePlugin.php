<?php

namespace Drupal\field_inheritance\Plugin\FieldInheritance;

use Drupal\field_inheritance\FieldInheritancePluginInterface;

/**
 * String Inheritance plugin.
 *
 * @FieldInheritance(
 *   id = "string_inheritance",
 *   name = @Translation("String Field Inheritance"),
 *   types = {
 *     "string",
 *     "string_long"
 *   }
 * )
 */
class StringFieldInheritancePlugin extends FieldInheritancePluginBase implements FieldInheritancePluginInterface {
}
