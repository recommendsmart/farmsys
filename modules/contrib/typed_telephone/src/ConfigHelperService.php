<?php

namespace Drupal\typed_telephone;

use Drupal\webprofiler\Config\ConfigFactoryWrapper;

/**
 * Helper class to avoid repeating loading or transforming values from config.
 */
class ConfigHelperService {

  /**
   * Drupal\webprofiler\Config\ConfigFactoryWrapper definition.
   *
   * @var \Drupal\webprofiler\Config\ConfigFactoryWrapper
   */
  protected $configFactory;

  /**
   * Constructs a new ConfigHelperService object.
   */
  public function __construct(ConfigFactoryWrapper $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Loads all TelephoneType entities from config and transform to simple array.
   */
  public function getTypes() {
    $final_types = [];
    $teltypes = $this->configFactory->listAll('typed_telephone.telephone_type');
    foreach ($teltypes as $teltype) {
      $type_config = $this->configFactory->get($teltype);
      $final_types[$type_config->get('id')] = [
        'label' => $type_config->get('label'),
        'uuid'  => $type_config->get('uuid'),
      ];
    }

    return $final_types;
  }

  /**
   * Retrieves label for a given TelephoneType machine name.
   */
  public function getLabelFromShortname($name) {
    return $this->configFactory->get('typed_telephone.telephone_type.' . $name)->get('label');
  }

  /**
   * Transforms all TelephoneType entites into an #options form element array.
   */
  public function getTypesAsOptions($filter = []) {
    $types = $this->getTypes();

    // Filter out options not selected.
    if (!empty($filter)) {
      $types = array_intersect_key($types, array_flip($filter));
    }

    // Reformat remaining array items to checkboxes #options format.
    foreach ($types as &$type) {
      $type = $type['label'];
    }

    return $types;
  }

}
