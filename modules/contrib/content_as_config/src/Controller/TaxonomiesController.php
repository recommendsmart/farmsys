<?php

namespace Drupal\content_as_config\Controller;

/**
 * Controller for syncing taxonomy terms.
 */
class TaxonomiesController extends EntityControllerBase {

  const ENTITY_TYPE = 'taxonomy_term';
  const FIELD_NAMES = [
    'vid',
    'name',
    'langcode',
    'description',
    'weight',
    'parent',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getExportableEntities(?array $export_list): array {
    $entities = [];
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    if (isset($export_list)) {
      $export_list = array_filter($export_list, 'is_string');
      if (!empty($export_list)) {
        $entities = $storage->loadByProperties(['vid' => $export_list]);
      }
    }
    else {
      $entities = $storage->loadMultiple();
    }
    return $entities;
  }

}
