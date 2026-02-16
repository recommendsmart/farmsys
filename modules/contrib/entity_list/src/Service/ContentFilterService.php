<?php

namespace Drupal\entity_list\Service;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\entity_list\Entity\EntityList;

/**
 * Class ContentFilterService.
 */
class ContentFilterService {

  /**
   * Drupal\Core\Entity\EntityFieldManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * ContentFilterService constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   This is entity field manager.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Get fields term of filter.
   *
   * @param EntityList $entity_list
   *   This is current entity list.
   *
   * @return array
   *   Return array of fields of filter.
   */
  public function getFieldsTermsFilter(EntityList $entity_list) {
    if (!empty($entity_list->getEntityListQueryPlugin()) && $entity_list->getEntityListQueryPlugin()->getEntityTypeId() === 'node') {
      $bundles = $entity_list->getEntityListQueryPlugin()->getBundles();
      $fields = [];

      foreach ($bundles as $bundle) {
        if ($bundle) {
          $node_base_fields = $this->entityFieldManager->getBaseFieldDefinitions('node');
          $node_fields = $this->entityFieldManager->getFieldDefinitions('node', $bundle);
          $node_custom_fields = array_diff_key($node_fields, $node_base_fields);

          foreach ($node_custom_fields as $key => $custom_field) {
            if ($custom_field->getType() === 'entity_reference' && $custom_field->getSetting('target_type') === 'taxonomy_term') {
              if (!empty($custom_field->getSetting('handler_settings'))) {
                $field_vocabularies = array_keys($custom_field->getSetting('handler_settings')['target_bundles']);
              }

              if (isset($fields[$key])) {
                $fields[$key]['bundles'][$bundle] = $bundle;
              }
              else {
                $fields[$key] = [
                  'bundles' => [$bundle => $bundle],
                  'key' => $key,
                  'label' => $custom_field->getLabel(),
                  'vocabulary' => isset($field_vocabularies) ? reset($field_vocabularies) : '',
                  'type' => $custom_field->getType(),
                ];
              }
            }
          }
        }
      }

      return $fields;
    }

    return [];
  }

  /**
   * Get fields text of filter.
   *
   * @param EntityList $entity_list
   *   This is current entity list.
   *
   * @return array
   *   Return array of fields of filter.
   */
  public function getFilterTextFields(EntityList $entity_list) {
    if (!empty($entity_list->getEntityListQueryPlugin()) && $entity_list->getEntityListQueryPlugin()->getEntityTypeId() === 'node') {
      $bundles = $entity_list->getEntityListQueryPlugin()->getBundles();
      $fields = [];

      foreach ($bundles as $bundle) {
        if ($bundle) {
          $node_fields = $this->entityFieldManager->getFieldDefinitions('node', $bundle);

          foreach ($node_fields as $key => $field) {
            $string_types = [
              'string',
              'string_long',
              'text_with_summary',
              'text',
              'text_long',
            ];

            if (in_array($field->getType(), $string_types)) {
              if (isset($fields[$key])) {
                $fields[$key]['bundles'][$bundle] = $bundle;
              }
              else {
                $fields[$key] = [
                  'bundles' => [$bundle => $bundle],
                  'key' => $key,
                  'label' => $field->getLabel(),
                  'type' => $field->getType(),
                ];
              }
            }
          }
        }
      }

      if (!empty($fields['revision_log'])) {
        unset($fields['revision_log']);
      }

      return $fields;
    }

    return [];
  }

  /**
   * Get fields date of filter.
   *
   * @param EntityList $entity_list
   *   This is current entity list.
   *
   * @return array
   *   Return array of fields of filter.
   */
  public function getFilterDateFields(EntityList $entity_list) {
    if (!empty($entity_list->getEntityListQueryPlugin()) && $entity_list->getEntityListQueryPlugin()->getEntityTypeId() === 'node') {
      $bundles = $entity_list->getEntityListQueryPlugin()->getBundles();
      $fields = [];

      foreach ($bundles as $bundle) {
        if ($bundle) {
          $node_fields = $this->entityFieldManager->getFieldDefinitions('node', $bundle);

          foreach ($node_fields as $key => $field) {
            $date_types = [
              'created',
              'changed',
              'datetime',
              'daterange',
            ];

            if (in_array($field->getType(), $date_types)) {
              if (isset($fields[$key])) {
                $fields[$key]['bundles'][$bundle] = $bundle;
              }
              else {
                $fields[$key] = [
                  'bundles' => [$bundle => $bundle],
                  'key' => $key,
                  'label' => $field->getLabel(),
                  'type' => $field->getType(),
                ];
              }
            }
          }
        }
      }

      if (!empty($fields['revision_timestamp'])) {
        unset($fields['revision_timestamp']);
      }

      return $fields;
    }

    return [];
  }

  /**
   * Get field sortable in current node selected.
   *
   * @param EntityList $entity_list
   *   This is current entity list.
   *
   * @return array
   *   Return array of fields in node selected.
   */
  public function getFieldSortable(EntityList $entity_list) {
    return $this->getAllField($entity_list);
  }

  /**
   * Get all field current node selected.
   *
   * @param EntityList $entity_list
   *   This is current entity list.
   *
   * @return array
   *   Return array of fields in node selected.
   */
  public function getAllField(EntityList $entity_list) {
    if (!empty($entity_list->getEntityListQueryPlugin()) && $entity_list->getEntityListQueryPlugin()->getEntityTypeId() === 'node') {
      $bundles = $entity_list->getEntityListQueryPlugin()->getBundles();
      $fields = [];

      foreach ($bundles as $bundle) {
        if ($bundle) {
          $node_fields = $this->entityFieldManager->getFieldDefinitions('node', $bundle);

          foreach ($node_fields as $key => $field) {
            if (!isset($fields[$key])) {
              $fields[$key] = [
                'key' => $key,
                'label' => $field->getLabel(),
                'type' => $field->getType(),
              ];
            }
          }
        }
      }

      return $fields;
    }

    return [];
  }

}
