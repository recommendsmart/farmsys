<?php

namespace Drupal\entity_list\Plugin;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_list\Element\RegionTable;
use Drupal\entity_list\Service\ContentFilterService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Entity list extra display plugins.
 */
abstract class EntityListExtraDisplayBase extends PluginBase implements EntityListExtraDisplayInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The current entity list object.
   *
   * @var \Drupal\entity_list\Entity\EntityListInterface
   */
  protected $entity;

  /**
   * The current display settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * The entity list filter manager.
   *
   * @var \Drupal\entity_list\Plugin\EntityListFilterManager
   */
  protected $entityListFilterManager;

  /**
   * The content filter service.
   *
   * @var \Drupal\entity_list\Service\ContentFilterService
   */
  protected $contentFilterService;

  /**
   * The entity list sortable filter manager.
   *
   * @var \Drupal\entity_list\Plugin\EntityListSortableFilterManager
   */
  protected $entityListSortableFilterManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityListFilterManager $entity_list_filter_manager, EntityListSortableFilterManager $entity_list_sortable_filter_manager, ContentFilterService $content_filter_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entity = $configuration['entity'];
    $this->settings = $configuration['settings'];
    $this->entityListFilterManager = $entity_list_filter_manager;
    $this->entityListSortableFilterManager = $entity_list_sortable_filter_manager;
    $this->contentFilterService = $content_filter_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.entity_list_filter'),
      $container->get('plugin.manager.entity_list_sortable_filter'),
      $container->get('service.content_filter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(FormStateInterface $form_state) {
    return [];
  }

  /**
   * The #region_callback callback used by the region_table form element.
   *
   * @param array $row
   *   The current table row.
   *
   * @return string
   *   The region for the current row.
   */
  public static function getRowRegion(array &$row) {
    $regions = self::getRegionOptions();
    if (!isset($regions[$row['region']['#value']])) {
      $row['region']['#value'] = 'disable';
    }
    return $row['region']['#value'];
  }

  /**
   * Get the table header used in the layout section.
   *
   * @return array
   *   An array of string or translatable markup.
   */
  protected function getLayoutHeader() {
    return [
      $this->t('Label'),
      $this->t('Weight'),
      $this->t('Region'),
      $this->t('Settings'),
    ];
  }

  /**
   * Build regions.
   *
   * @return array
   */
  public function buildRegions() {
    $regions = [];

    $regions['filters'] = RegionTable::buildRowRegion($this->t('Filters'), $this->t('Empty region'));
    $regions['disable'] = RegionTable::buildRowRegion($this->t('Disable'), $this->t('No items disabled.'));

    return $regions;
  }

  /**
   * Get regions options.
   *
   * @return array
   */
  public static function getRegionOptions() {
    $regions = [];

    $regions['filters'] = t('Filters');
    $regions['disable'] = t('Disable');

    return $regions;
  }

  /**
   * Build the tabledrag array.
   *
   * @param array $group_classes
   *   An array containing region/weight classes.
   * @param array $regions
   *   AN array of regions.
   *
   * @return array
   *   An array representing the tabledrag values.
   */
  protected function buildTableDrag(array $group_classes, array $regions) {
    $tabledrags = [];

    foreach ($regions as $region_name => $region) {
      $region_name_class = Html::getClass($region_name);
      $tabledrags[] = [
        'action' => 'match',
        'hidden' => TRUE,
        'relationship' => 'sibling',
        'group' => $group_classes['region'],
        'subgroup' => "{$group_classes['region']}-$region_name_class",
      ];
      $tabledrags[] = [
        'action' => 'order',
        'hidden' => TRUE,
        'relationship' => 'sibling',
        'group' => $group_classes['weight'],
        'subgroup' => "{$group_classes['weight']}-$region_name_class",
      ];
    }

    return $tabledrags;
  }

}
