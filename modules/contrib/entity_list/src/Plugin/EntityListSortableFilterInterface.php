<?php

namespace Drupal\entity_list\Plugin;

use Drupal\entity_list\Entity\EntityList;

/**
 * Provides an interface for a EntityListSortableFilterInterface.
 *
 * @ingroup form_api
 */
interface EntityListSortableFilterInterface {

  /**
   * Render filter with form api.
   *
   * @param array $parameters
   *   This is parameters of fields.
   * @param EntityList $entity_list
   *   This is current entity list.
   *
   * @return array
   *   The form structure.
   */
  public function buildFilter(array $parameters, EntityList $entity_list);

  /**
   * Create render array of configuration filter with form api.
   *
   * @param array $default_value
   *   This is default values of fields.
   * @param EntityList $entity_list
   *   This is current entity list.
   *
   * @return array
   *   The form structure.
   */
  public function configurationFilter(array $default_value, EntityList $entity_list);

  /**
   * Create array of fields for mapping fields with query.
   *
   * @param array $settings
   *  This is settings of filter.
   *
   * @return array
   *  Return array of fields.
   */
  public function setFields(array $settings);
}
