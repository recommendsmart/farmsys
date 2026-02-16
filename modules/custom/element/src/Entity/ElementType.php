<?php

namespace Drupal\element\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityDescriptionInterface;

/**
 * Defines the element type entity.
 *
 * @ConfigEntityType(
 *   id = "element_type",
 *   label = @Translation("Element type"),
 *   label_collection = @Translation("Element types"),
 *   label_singular = @Translation("element type"),
 *   label_plural = @Translation("element types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count element type",
 *     plural = "@count element types",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\element\ElementTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\element\Form\ElementTypeForm",
 *       "edit" = "Drupal\element\Form\ElementTypeForm",
 *       "delete" = "Drupal\element\Form\ElementTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "element_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "element",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/element-types/{element_type}",
 *     "add-form" = "/admin/structure/element-types/add",
 *     "edit-form" = "/admin/structure/element-types/{element_type}/edit",
 *     "delete-form" = "/admin/structure/element-types/{element_type}/delete",
 *     "auto-label" = "/admin/structure/element-types/{element_type}/auto-label",
 *     "collection" = "/admin/structure/element-types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description"
 *   }
 * )
 */
class ElementType extends ConfigEntityBundleBase implements ElementTypeInterface, EntityDescriptionInterface {

  /**
   * The element type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The element type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The element type description.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
  }

}
