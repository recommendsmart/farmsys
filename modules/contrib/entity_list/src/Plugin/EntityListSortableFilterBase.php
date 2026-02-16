<?php

namespace Drupal\entity_list\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_list\Entity\EntityList;

/**
 * Class FilterFormBase.
 */
abstract class EntityListSortableFilterBase extends PluginBase implements EntityListSortableFilterInterface {

  use StringTranslationTrait;

  /**
   * @var array
   */
  public array $fields = [];

  /**
   * {@inheritdoc}
   */
  public function buildFilter(array $parameters, EntityList $entity_list) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setFields(array $settings) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFilter(array $default_value, EntityList $entity_list) {
    return [
      '#type' => 'details',
      '#title' => $this->t('Settings'),
      '#open' => FALSE,
      '#tree' => TRUE,
    ];
  }

}
