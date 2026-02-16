<?php

namespace Drupal\content_as_config\Controller;

/**
 * Controller for syncing taxonomy terms.
 */
class MenuLinksController extends EntityControllerBase {

  const ENTITY_TYPE = 'menu_link_content';
  const FIELD_NAMES = [
    'menu_name',
    'title',
    'parent',
    'link',
    'description',
    'enabled',
    'expanded',
    'weight',
    'langcode',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getExportableEntities(?array $export_list): array {
    $entities = [];
    $storage = $this->entityTypeManager->getStorage('menu_link_content');
    if (isset($export_list)) {
      $export_list = array_filter($export_list, 'is_string');
      if (!empty($export_list)) {
        $entities = $storage->loadByProperties(['menu_name' => $export_list]);
      }
    }
    else {
      $entities = $storage->loadMultiple();
    }
    return $entities;
  }

}
