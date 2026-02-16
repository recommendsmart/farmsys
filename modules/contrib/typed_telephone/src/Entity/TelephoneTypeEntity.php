<?php

namespace Drupal\typed_telephone\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Telephone type entity.
 *
 * @ConfigEntityType(
 *   id = "telephone_type",
 *   label = @Translation("Telephone type"),
 *   handlers = {
 *     "list_builder" = "Drupal\typed_telephone\TelephoneTypeEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\typed_telephone\Form\TelephoneTypeEntityForm",
 *       "edit" = "Drupal\typed_telephone\Form\TelephoneTypeEntityForm",
 *       "delete" = "Drupal\typed_telephone\Form\TelephoneTypeEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\typed_telephone\TelephoneTypeEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "telephone_type",
 *   admin_permission = "administer site configuration",
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/content/telephone_type/{telephone_type}",
 *     "add-form" = "/admin/config/content/telephone_type/add",
 *     "edit-form" = "/admin/config/content/telephone_type/{telephone_type}/edit",
 *     "delete-form" = "/admin/config/content/telephone_type/{telephone_type}/delete",
 *     "collection" = "/admin/config/content/telephone_type"
 *   }
 * )
 */
class TelephoneTypeEntity extends ConfigEntityBase implements TelephoneTypeEntityInterface {

  /**
   * The Telephone type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Telephone type label.
   *
   * @var string
   */
  protected $label;

}
