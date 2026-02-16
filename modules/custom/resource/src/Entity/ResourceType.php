<?php

/**
 * @file
 * Contains \Drupal\resource\Entity\ResourceType.
 */

namespace Drupal\resource\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\resource\ResourceTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Resource type entity.
 *
 * @ConfigEntityType(
 *   id = "resource_type",
 *   label = @Translation("Resource types"),
 *   handlers = {
 *     "access" = "Drupal\resource\ResourceTypeAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\resource\Form\ResourceTypeForm",
 *       "edit" = "Drupal\resource\Form\ResourceTypeForm",
 *       "delete" = "Drupal\resource\Form\ResourceTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\resource\ResourceTypeListBuilder",
 *   },
 *   admin_permission = "administer site configuration",
 *   config_prefix = "type",
 *   bundle_of = "resource",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/resource_type/{resource_type}",
 *     "edit-form" = "/admin/structure/resource_type/{resource_type}/edit",
 *     "delete-form" = "/admin/structure/resource_type/{resource_type}/delete",
 *     "collection" = "/admin/structure/resource_type"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "name_pattern",
 *     "name_edit",
 *     "active",
 *     "new_revision",
 *   }
 * )
 */
class ResourceType extends ConfigEntityBundleBase implements ResourceTypeInterface {

  /**
   * The Resource type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Resource type label.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this resource type.
   *
   * @var string
   */
  protected $description;

  /**
   * Pattern for auto-generating the resource name, using tokens.
   *
   * @var string
   */
  protected $name_pattern;

  /**
   * Resource name is user editable.
   *
   * @var bool
   */
  protected $name_edit = FALSE;

  /**
   * Automatically mark resources of this type as active.
   *
   * @var bool
   */
  protected $active = FALSE;

  /**
   * Default value of the 'Create new revision' checkbox of this resource type.
   *
   * @var bool
   */
  protected $new_revision = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getNamePattern() {
    return $this->name_pattern;
  }

  /**
   * {@inheritdoc}
   */
  public function isNameEditable() {
    return $this->name_edit;
  }

  /**
   * {@inheritdoc}
   */
  public function isAutomaticallyActive() {
    return $this->active;
  }

  /**
   * {@inheritdoc}
   */
  public function isNewRevision() {
    return $this->new_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if ($update && $this->getOriginalId() != $this->id()) {
      $update_count = node_type_update_nodes($this->getOriginalId(), $this->id());
      if ($update_count) {
        drupal_set_message(\Drupal::translation()->formatPlural($update_count,
          'Changed the resource type of 1 post from %old-type to %type.',
          'Changed the resource type of @count posts from %old-type to %type.',
          array(
            '%old-type' => $this->getOriginalId(),
            '%type' => $this->id(),
          )));
      }
    }
    if ($update) {
      // Clear the cached field definitions as some settings affect the field
      // definitions.
      $this->entityManager()->clearCachedFieldDefinitions();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Clear the node type cache to reflect the removal.
    $storage->resetCache(array_keys($entities));
  }
}
