<?php

namespace Drupal\field_inheritance\Plugin\FieldInheritance;

use Drupal\field_inheritance\FieldInheritancePluginInterface;

/**
 * Date Inheritance plugin.
 *
 * @FieldInheritance(
 *   id = "date_inheritance",
 *   name = @Translation("Date Field Inheritance"),
 *   types = {
 *     "datetime",
 *     "daterange",
 *   }
 * )
 */
class DateFieldInheritancePlugin extends FieldInheritancePluginBase implements FieldInheritancePluginInterface {
}
