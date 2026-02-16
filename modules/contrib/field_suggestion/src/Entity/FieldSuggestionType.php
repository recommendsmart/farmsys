<?php

namespace Drupal\field_suggestion\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\field_suggestion\FieldSuggestionTypeInterface;

/**
 * Defines the Field suggestion type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "field_suggestion_type",
 *   label = @Translation("Field suggestion type"),
 *   label_collection = @Translation("Field suggestion types"),
 *   label_singular = @Translation("Field suggestion type"),
 *   label_plural = @Translation("Field suggestion types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count field suggestion type",
 *     plural = "@count field suggestion types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\field_suggestion\Form\FieldSuggestionTypeForm",
 *       "edit" = "Drupal\field_suggestion\Form\FieldSuggestionTypeForm",
 *     },
 *     "list_builder" = "Drupal\field_suggestion\FieldSuggestionTypeListBuilder",
 *   },
 *   admin_permission = "administer field suggestion",
 *   config_prefix = "type",
 *   bundle_of = "field_suggestion",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/field-suggestion",
 *     "edit-form" = "/admin/structure/field-suggestion/manage/{field_suggestion_type}",
 *   },
 *   config_export = {
 *     "id",
 *   }
 * )
 */
class FieldSuggestionType extends ConfigEntityBundleBase implements FieldSuggestionTypeInterface {

  /**
   * The machine name of this field suggestion type.
   *
   * @var string
   */
  protected $id;

  /**
   * {@inheritdoc}
   */
  public function label() {
    /** @var \Drupal\Core\Field\FieldTypePluginManagerInterface $manager */
    $manager = \Drupal::service('plugin.manager.field.field_type');

    return $manager->getDefinition($this->id())['label'];
  }

}
