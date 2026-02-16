<?php

namespace Drupal\collection\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Collection type entity.
 *
 * @ConfigEntityType(
 *   id = "collection_type",
 *   label = @Translation("Collection type"),
 *   label_collection = @Translation("Collection types"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\collection\CollectionTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\collection\Form\CollectionTypeForm",
 *       "edit" = "Drupal\collection\Form\CollectionTypeForm",
 *       "delete" = "Drupal\collection\Form\CollectionTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer site configuration",
 *   bundle_of = "collection",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/collection/add",
 *     "edit-form" = "/admin/structure/collection/{collection_type}",
 *     "delete-form" = "/admin/structure/collection/{collection_type}/delete",
 *     "collection" = "/admin/structure/collection"
 *   },
 *   config_prefix = "collection_type",
 *   config_export = {
 *     "id",
 *     "label",
 *     "allowed_collection_item_types",
 *   }
 * )
 */
class CollectionType extends ConfigEntityBundleBase implements CollectionTypeInterface {

  /**
   * The Collection type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Collection type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Collection type allow collection item types.
   *
   * @var array
   */
  protected $allowed_collection_item_types = [];

  /**
   * {@inheritdoc}
   */
  public function getAllowedCollectionItemTypes($entity_type_id = NULL, $bundle = NULL) {
    $allowed_collection_item_types = [];

    if (!$entity_type_id && !$bundle) {
      $allowed_collection_item_types = $this->allowed_collection_item_types;
    }
    else {
      $collection_item_type_storage = \Drupal::service('entity_type.manager')->getStorage('collection_item_type');

      foreach ($this->allowed_collection_item_types as $allowed_collection_item_type_id) {
        $allowed_collection_item_type = $collection_item_type_storage->load($allowed_collection_item_type_id);

        if (!$allowed_collection_item_type) {
          continue;
        }

        foreach ($allowed_collection_item_type->getAllowedBundles() as $entity_and_bundle) {
          list($allowed_entity_type_id, $allowed_bundle) = explode('.', $entity_and_bundle);

          // If this collection item type is already allowed, don't check again.
          if (in_array($allowed_collection_item_type_id, $allowed_collection_item_types)) {
            continue;
          }

          if ($entity_type_id && $bundle && $entity_type_id === $allowed_entity_type_id && $bundle === $allowed_bundle) {
            $allowed_collection_item_types[] = $allowed_collection_item_type_id;
          }
          elseif (!$bundle && $entity_type_id && $entity_type_id === $allowed_entity_type_id) {
            $allowed_collection_item_types[] = $allowed_collection_item_type_id;
          }
        }
      }
    }

    return $allowed_collection_item_types;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedEntityBundles($entity_type_id = NULL) {
    $entity_bundles = [];
    $collection_item_type_storage = \Drupal::service('entity_type.manager')->getStorage('collection_item_type');

    foreach ($this->allowed_collection_item_types as $allowed_collection_item_type_id) {
      $collection_item_type = $collection_item_type_storage->load($allowed_collection_item_type_id);

      foreach ($collection_item_type->getAllowedBundles() as $entity_and_bundle) {
        list($entity_type, $bundle_name) = explode('.', $entity_and_bundle);

        if ($entity_type_id) {
          if ($entity_type_id !== $entity_type) {
            continue;
          }

          if (isset($entity_bundles[$entity_type])) {
            if (!in_array($bundle_name, $entity_bundles[$entity_type])) {
              $entity_bundles[$entity_type][$bundle_name] = $bundle_name;
            }
          }
          else {
            $entity_bundles[$entity_type][$bundle_name] = $bundle_name;
          }
        }
        else {
          $entity_bundles[$entity_type][$bundle_name] = $bundle_name;
        }
      }
    }

    return $entity_bundles;
  }
}
