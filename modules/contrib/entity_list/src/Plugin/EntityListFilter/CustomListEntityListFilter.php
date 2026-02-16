<?php

namespace Drupal\entity_list\Plugin\EntityListFilter;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\entity_list\Entity\EntityList;
use Drupal\entity_list\Service\ContentFilterService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class CustomListEntityListFilter.
 *
 * @package Drupal\entity_list\Plugin
 *
 * @EntityListFilter(
 *   id = "custom_list_entity_list_filter",
 *   label = @Translation("Custom List Entity List Filter"),
 *   content_type = {},
 *   entity_type = {
 *     "node"
 *   },
 * )
 */
class CustomListEntityListFilter extends TaxonomiesEntityListFilter {

  /**
   * Drupal\entity_list\Service\ContentFilterService
   *
   * @var \Drupal\entity_list\Service\ContentFilterService
   */
  protected $contentFilterService;

  /**
   * TaxonomiesEntityListFilter constructor.
   *
   * @param $configuration
   *   This is configuration.
   * @param $plugin_id
   *   This is plugin id.
   * @param $plugin_definition
   *   This is plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   This is entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   This is language manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   This is entity repository interface.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   This is request stack.
   * @param \Drupal\entity_list\Service\ContentFilterService $content_filter_service
   *   This is content filter service.
   */
  public function __construct(
    $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager,
    EntityRepositoryInterface $entity_repository,
    RequestStack $request_stack,
    ModuleHandler $module_handler,
    ContentFilterService $content_filter_service){
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager,
      $language_manager,
      $entity_repository,
      $request_stack,
      $module_handler
    );
    $this->contentFilterService = $content_filter_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('entity.repository'),
      $container->get('request_stack'),
      $container->get('module_handler'),
      $container->get('service.content_filter'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildFilter(array $parameters, EntityList $entity_list) {
    $filter = [];
    $request = $this->requestStack->getCurrentRequest();
    $options = [];

    if (!empty($parameters['settings']['allowed_values'])) {
      $list = explode("\n", $parameters['settings']['allowed_values']);

      foreach ($list as $value) {
        $element = explode("|", $value);

        if (!empty($element)) {
          $options[$element[0]] = $this->t($element[1]);
        }
      }
    }

    switch ($parameters['settings']['widget']) {
      case 'select':
        $filter[$parameters['settings']['id']] = [
          '#type' => 'select',
          '#multiple' => $parameters['settings']['cardinality'],
          '#title_display' => $parameters['settings']['title_display'] ? 'visible' : 'invisible',
          '#title' => $this->t($parameters['settings']['title']),
          '#empty_option' => $this->t('- Select -'),
          '#default_value' => $request->get($parameters['settings']['id'], []),
          '#options' => $options,
        ];
        break;

      case 'checkbox':
        $filter[$parameters['settings']['id']] = [
          '#type' => $parameters['settings']['cardinality'] ? 'checkboxes' : 'radios',
          '#title_display' => $parameters['settings']['title_display'] ? 'visible' : 'invisible',
          '#title' => $this->t($parameters['settings']['title']),
          '#default_value' => $request->get($parameters['settings']['id'], []),
          '#options' => $options,
        ];
        break;
    }

    if ($parameters['settings']['collapsible'] && $this->moduleHandler->moduleExists('fapi_collapsible')) {
      return $this->addCollapsible(
        $parameters['settings']['title'],
        $filter,
        $parameters['settings']['id'] . 'collapsible',
        !empty(self::convertValues($request->get($parameters['settings']['id'], FALSE))),
        $parameters['settings']['expanded'],
      );
    }

    return $filter;
  }



  /**
   * {@inheritdoc}
   */
  public function configurationFilter(array $default_value, EntityList $entity_list) {
    $configuration = parent::configurationFilter($default_value, $entity_list);
    unset($configuration['order']);
    unset($configuration['exclude']);

    $configuration['field_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Field Name'),
      '#default_value' => $default_value['field_name'] ?? '',
      '#options' => $this->getFieldNameOptions($entity_list),
      '#required' => TRUE,
    ];

    $configuration['allowed_values'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed Values'),
      '#default_value' => $default_value['allowed_values'] ?? '',
      '#required' => TRUE,
      '#description' => $this->t('You need to set values like: id|value per row like list_text'),
    ];

    return $configuration;
  }

  /**
   * Get fields names options
   *
   * @param \Drupal\entity_list\Entity\EntityList $entity_list
   *   This is entity list.
   * @return array
   *   Return array of fields names options.
   */
  public function getFieldNameOptions(EntityList $entity_list) {
    return array_map(function ($element) {
      return $element['label'];
    }, $this->contentFilterService->getAllField($entity_list));
  }

}
