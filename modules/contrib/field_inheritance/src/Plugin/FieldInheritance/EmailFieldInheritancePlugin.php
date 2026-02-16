<?php

namespace Drupal\field_inheritance\Plugin\FieldInheritance;

use Drupal\field_inheritance\FieldInheritancePluginInterface;

/**
 * Email Inheritance plugin.
 *
 * @FieldInheritance(
 *   id = "email_inheritance",
 *   name = @Translation("Email Field Inheritance"),
 *   types = {
 *     "email"
 *   }
 * )
 */
class EmailFieldInheritancePlugin extends FieldInheritancePluginBase implements FieldInheritancePluginInterface {
}
