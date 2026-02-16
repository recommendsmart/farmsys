<?php

namespace Drupal\entity_list\Plugin\EntityListFilter;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\Query\Sql\Query;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_list\Entity\EntityList;
use Drupal\entity_list\Plugin\EntityListFilterBase;
use Drupal\entity_list\Service\ContentFilterService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class DateEntityListFilter.
 *
 * @package Drupal\entity_list\Plugin
 *
 * @EntityListFilter(
 *   id = "date_entity_list_filter",
 *   label = @Translation("Date Entity List Filter"),
 *   content_type = {},
 *   entity_type = {},
 * )
 */
class DateEntityListFilter extends EntityListFilterBase implements ContainerFactoryPluginInterface {

  const FIELD_DATE_END = 'created_end';
  const FIELD_DATE = 'date';
  const FIELD_DATE_START = 'created_start';

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack RequestStack
   */
  protected $requestStack;

  /**
   * Drupal\entity_list\Service\ContentFilterService definition.
   *
   * @var \Drupal\entity_list\Service\ContentFilterService
   */
  protected $contentFilterService;

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs DateEntityListFilter object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service used to get url query parameters.
   * @param \Drupal\entity_list\Service\ContentFilterService $content_filter_service
   *   The content filter service.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   This is module handler.
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, RequestStack $request_stack, ContentFilterService $content_filter_service, ModuleHandler $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
    $this->contentFilterService = $content_filter_service;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('request_stack'),
      $container->get('service.content_filter'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildFilter(array $parameters, EntityList $entity_list) {
    $filter = parent::buildFilter($parameters, $entity_list);
    $request = $this->requestStack->getCurrentRequest();

    $filter['fieldset-date'] = [
      '#type' => 'fieldset',
      '#title' => $parameters['settings']['title'],
    ];

    if (!$parameters['settings']['title_display']) {
      $filter['fieldset-date']['#title_display'] = 'invisible';
    }

    if ($parameters['settings']['range']) {
      $filter['fieldset-date'][self::FIELD_DATE_START] = [
        '#type' => 'date',
        '#title' => $this->t($parameters['settings']['title']) ?? $this->t('Start date'),
        '#default_value' => $request->get(self::FIELD_DATE_START, []),
      ];

      $filter['fieldset-date'][self::FIELD_DATE_END] = [
        '#type' => 'date',
        '#title' => $this->t($parameters['settings']['end_title']) ?? $this->t('End date'),
        '#default_value' => $request->get(self::FIELD_DATE_END, []),
      ];
    }
    else {
      $filter['fieldset-date'][self::FIELD_DATE] = [
        '#type' => 'date',
        '#title_display' => 'invisible',
        '#title' => $this->t($parameters['settings']['title']),
        '#default_value' => $request->get(self::FIELD_DATE, []),
      ];
    }

    if ($parameters['settings']['collapsible'] && $this->moduleHandler->moduleExists('fapi_collapsible')) {
      return $this->addCollapsible(
        $parameters['settings']['title'],
        $filter,
        $parameters['settings']['id'] . 'collapsible',
        $this->asValue($request),
        $parameters['settings']['expanded'],
      );
    }

    return $filter;
  }

  /**
   * As value.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   This is request.
   *
   * @return bool
   *   Return as value.
   */
  public function asValue(Request $request) {
    return $request->get(self::FIELD_DATE, []) || $request->get(self::FIELD_DATE_END, []) || $request->get(self::FIELD_DATE_START, []);
  }

  /**
   * {@inheritdoc}
   */
  public function setFields(array $settings) {
    if ($settings['range']) {
      $this->fields = [
        [
          'name' => self::FIELD_DATE_END,
          'operator' => $settings['end_operators'] ?? '',
          'callback_condition' => __CLASS__ . '::dateRangeFilter',
          'field' => $settings['field_date_range'] ?? [],
          'field_type' => $settings['field_type_date_range'] ?? '',
        ],
        [
          'name' => self::FIELD_DATE_START,
          'operator' => $settings['operators'] ?? '',
          'callback_condition' => __CLASS__ . '::dateRangeFilter',
          'field' => $settings['fields'] ?? [],
          'field_type' => $settings['field_type'] ?? '',
        ],
      ];
    }
    else {
      $this->fields = [
        [
          'name' => self::FIELD_DATE,
          'callback_condition' => __CLASS__ . '::dateFilter',
          'field' => $settings['fields'] ?? [],
          'operator' => $settings['operators'] ?? '',
          'end_operator' => $settings['field_type'] === 'daterange' ? $settings['end_operators'] : '',
          'field_type' => $settings['field_type'] ?? '',
        ],
      ];

    }

    return $this->fields;
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFilter(array $default_value, EntityList $entity_list) {
    $configuration = parent::configurationFilter($default_value, $entity_list);
    $date_fields = $this->contentFilterService->getFilterDateFields($entity_list);
    $options = $this->fieldDateToOption($date_fields);

    $configuration['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $default_value['title'] ?? '',
      '#required' => TRUE,
    ];

    $configuration['fields'] = [
      '#type' => 'select',
      '#title' => $this->t('Fields'),
      '#default_value' => $default_value['fields'] ?? [],
      '#required' => TRUE,
      '#ajax' => [
        'callback' => ['\Drupal\entity_list\Plugin\EntityListFilter\DateEntityListFilter', 'setFieldTypeAjax'],
      ],
      '#options' => $options,
    ];

    $configuration['field_type'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => 'field-type-date',
      ],
      '#value' => $default_value['field_type'] ?? $date_fields[array_key_first($date_fields)]['type'],
    ];

    $configuration['operators'] = [
      '#type' => 'select',
      '#title' => $this->t('Operators'),
      '#default_value' => $default_value['operators'] ?? [],
      '#options' => [
        '>=' => $this->t('Superior or equal'),
        '>' => $this->t('Superior'),
        '<=' => $this->t('Inferior or equal'),
        '<' => $this->t('Inferior'),
        '=' => $this->t('Equal'),
      ],
      '#wrapper_attributes' => [
        'id' => 'field-operators',
      ],
    ];

    $configuration['#fields_definitions'] = $date_fields;

    $configuration['range'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Date range'),
      '#description' => $this->t('If you check this box, we display two fields (start date & end date)'),
      '#default_value' => $default_value['range'] ?? FALSE,
      '#ajax' => [
        'callback' => ['\Drupal\entity_list\Plugin\EntityListFilter\DateEntityListFilter', 'enabledOperatorsAjax'],
      ],
    ];

    $configuration['end_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('End Title'),
      '#default_value' => $default_value['end_title'] ?? '',
      '#wrapper_attributes' => [
        'id' => 'field-end-title',
      ],
    ];

    $configuration['field_date_range'] = [
      '#type' => 'select',
      '#title' => $this->t('End Field'),
      '#default_value' => $default_value['field_date_range'] ?? [],
      '#required' => TRUE,
      '#ajax' => [
        'callback' => ['\Drupal\entity_list\Plugin\EntityListFilter\DateEntityListFilter', 'setFieldDateRangeTypeAjax'],
      ],
      '#wrapper_attributes' => [
        'id' => 'field-date-range',
      ],
      '#options' => $options,
    ];

    $configuration['field_type_date_range'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => 'field-type-date-range',
      ],
      '#value' => $default_value['field_type_date_range'] ?? $date_fields[array_key_first($date_fields)]['type'],
    ];

    $configuration['end_operators'] = [
      '#type' => 'select',
      '#title' => $this->t('End Operators'),
      '#default_value' => $default_value['end_operators'] ?? [],
      '#wrapper_attributes' => [
        'id' => 'field-end-operators',
      ],
      '#options' => [
        '>=' => $this->t('Superior or equal'),
        '>' => $this->t('Superior'),
        '<=' => $this->t('Inferior or equal'),
        '<' => $this->t('Inferior'),
        '=' => $this->t('Equal'),
      ],
    ];

    $range = !empty($default_value['range']) ? boolval($default_value['range']) : 0;
    if (((isset($default_value['field_type']) && $default_value['field_type'] !== 'daterange') || empty($default_value)) && !$range) {
      $configuration['end_operators']['#wrapper_attributes']['class'] = ['hidden'];
      $configuration['end_title']['#wrapper_attributes']['class'] = ['hidden'];
      $configuration['field_date_range']['#wrapper_attributes']['class'] = ['hidden'];
    }

    return $configuration;
  }

  /**
   * Set field type date range in ajax.
   *
   * @param array $form
   *   This is current form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   This is current form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Return Ajax Response.
   */
  public static function setTypeAjax(array &$form, FormStateInterface $form_state, string $field_name = 'fields', string $field_type_name = 'field_type', string $id = '#field-type-date') {
    $response = new AjaxResponse();

    if (isset($form['settings'])) {
      $fields_definition = $form['settings']['#fields_definitions'];
      $field = $form_state->getValue(['settings', $field_name]);

      $form['settings'][$field_type_name]['#value'] = $fields_definition[$field]['type'];
    }
    else {
      $fields_definition = $form['filters_details']['filter']['filters_exposed']['layout_items']['date_entity_list_filter']['settings']['#fields_definitions'];
      $field = $form_state->getValue([
        'filter',
        'filters_exposed',
        'layout_items',
        'date_entity_list_filter',
        'settings',
        $field_name
      ]);

      $form['filters_details']['filter']['filters_exposed']['layout_items']['date_entity_list_filter']['settings'][$field_type_name]['#value'] = $fields_definition[$field]['type'];
    }

    self::setOperatorsAjax($response, $form, $form_state, $fields_definition[$field]['type']);

    $response->addCommand(new ReplaceCommand($id, $form['filters_details']['filter']['filters_exposed']['layout_items']['date_entity_list_filter']['settings'][$field_type_name]));
    return $response;
  }

  /**
   * Set field type date range in ajax.
   *
   * @param array $form
   *   This is form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   This is form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Return Ajax Response.
   */
  public static function setFieldDateRangeTypeAjax(array &$form, FormStateInterface $form_state) {
    return self::setTypeAjax($form, $form_state, 'field_date_range', 'field_type_date_range', '#field-type-date-range');
  }

  /**
   * Set field type date range in ajax.
   *
   * @param array $form
   *   This is form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   This is form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Return Ajax Response.
   */
  public static function setFieldTypeAjax(array &$form, FormStateInterface $form_state) {
    return self::setTypeAjax($form, $form_state);
  }

  /**
   * Enabled or not operators field.
   *
   * @param array $form
   *   This is current form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   This is current form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Return Ajax Response.
   */
  public static function enabledOperatorsAjax(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if (isset($form['settings'])) {
      $fields_definition = $form['settings']['#fields_definitions'];
      $field = $form_state->getValue(['settings', 'fields']);
    }
    else {
      $fields_definition = $form['filters_details']['filter']['filters_exposed']['layout_items']['date_entity_list_filter']['settings']['#fields_definitions'];
      $field = $form_state->getValue([
        'filter',
        'filters_exposed',
        'layout_items',
        'date_entity_list_filter',
        'settings',
        'fields'
      ]);
    }

    self::setOperatorsAjax($response, $form, $form_state, $fields_definition[$field]['type']);
    return $response;
  }

  /**
   * Set operators in ajax.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   This is ajax response.
   * @param array $form
   *   This is current form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   This is form state.
   * @param string $type
   *   This is type of field date.
   */
  public static function setOperatorsAjax(AjaxResponse $response, array &$form, FormStateInterface $form_state, string $type) {
    if (isset($form['settings'])) {
      $range = $form_state->getValue(['settings', 'range']);

      if ($type === 'daterange' || $range) {
        $form['settings']['end_title']['#wrapper_attributes']['class'] = [];
        $form['settings']['end_operators']['#wrapper_attributes']['class'] = [];
        $form['settings']['field_date_range']['#wrapper_attributes']['class'] = [];
      }
      else {
        $form['settings']['end_operators']['#wrapper_attributes']['class'] = ['hidden'];
        $form['settings']['end_title']['#wrapper_attributes']['class'] = ['hidden'];
        $form['settings']['field_date_range']['#wrapper_attributes']['class'] = ['hidden'];
      }

      $response->addCommand(new ReplaceCommand('#field-end-title', $form['settings']['end_title']));
      $response->addCommand(new ReplaceCommand('#field-end-operators', $form['settings']['end_operators']));
      $response->addCommand(new ReplaceCommand('#field-operators', $form['settings']['operators']));
      $response->addCommand(new ReplaceCommand('#field-date-range', $form['settings']['field_date_range']));
    }
    else {
      $range = $form_state->getValue([
        'filter',
        'filters_exposed',
        'layout_items',
        'date_entity_list_filter',
        'settings',
        'range'
      ]);

      if ($type === 'daterange' || $range) {
        $form['filters_details']['filter']['filters_exposed']['layout_items']['date_entity_list_filter']['settings']['end_operators']['#wrapper_attributes']['class'] = [];
        $form['filters_details']['filter']['filters_exposed']['layout_items']['date_entity_list_filter']['settings']['end_title']['#wrapper_attributes']['class'] = [];
        $form['filters_details']['filter']['filters_exposed']['layout_items']['date_entity_list_filter']['settings']['field_date_range']['#wrapper_attributes']['class'] = [];
      }
      else {
        $form['filters_details']['filter']['filters_exposed']['layout_items']['date_entity_list_filter']['settings']['end_operators']['#wrapper_attributes']['class'] = ['hidden'];
        $form['filters_details']['filter']['filters_exposed']['layout_items']['date_entity_list_filter']['settings']['end_title']['#wrapper_attributes']['class'] = ['hidden'];
        $form['filters_details']['filter']['filters_exposed']['layout_items']['date_entity_list_filter']['settings']['field_date_range']['#wrapper_attributes']['class'] = ['hidden'];
      }

      $response->addCommand(new ReplaceCommand('#field-end-title', $form['filters_details']['filter']['filters_exposed']['layout_items']['date_entity_list_filter']['settings']['end_title']));
      $response->addCommand(new ReplaceCommand('#field-end-operators', $form['filters_details']['filter']['filters_exposed']['layout_items']['date_entity_list_filter']['settings']['end_operators']));
      $response->addCommand(new ReplaceCommand('#field-operators', $form['filters_details']['filter']['filters_exposed']['layout_items']['date_entity_list_filter']['settings']['operators']));
      $response->addCommand(new ReplaceCommand('#field-date-range', $form['filters_details']['filter']['filters_exposed']['layout_items']['date_entity_list_filter']['settings']['field_date_range']));

    }
  }

  /**
   * Filter by date range.
   *
   * @param \Drupal\Core\Entity\Query\Sql\Query $query
   *   This is entity list query.
   * @param array $field_settings
   *   This is array of fields settings.
   * @param string|array $field_value
   *   This is field value.
   */
  public static function dateRangeFilter(Query $query, array $field_settings, $field_value) {
    $zone = new \DateTimeZone(date_default_timezone_get());
    $datetime = new \DateTime($field_value, $zone);
    $date = DrupalDateTime::createFromDateTime($datetime);

    if (in_array($field_settings['field_type'], ['created', 'changed'])) {
      $query->condition($field_settings['field'], $date->getTimestamp(), $field_settings['operator']);
    }
    else if (!in_array($field_settings['field_type'], ['created', 'changed', 'daterange'])) {
      $query->condition($field_settings['field'], $date->format(DateTimePlus::FORMAT), $field_settings['operator']);
    }
    else {
      $name = $field_settings['name'] === self::FIELD_DATE_END ? $field_settings['field'] . '.end_value' : $field_settings['field'];
      $query->condition($name, $date->format(DateTimePlus::FORMAT), $field_settings['operator']);
    }
  }

  /**
   * Filter by date.
   *
   * @param \Drupal\Core\Entity\Query\Sql\Query $query
   *   This is entity list query.
   * @param array $field_settings
   *   This is array of fields settings.
   * @param string|array $field_value
   *   This is field value.
   */
  public static function dateFilter(Query $query, array $field_settings, $field_value) {
    $zone = new \DateTimeZone(date_default_timezone_get());
    $datetime = new \DateTime($field_value, $zone);
    $date = DrupalDateTime::createFromDateTime($datetime);
    $operator = $fieldSettings['operator'] ?? '=';

    if (in_array($field_settings['field_type'], ['created', 'changed'])) {
      $query->condition($field_settings['field'], $date->getTimestamp(), $operator);
    }
    else if ($field_settings['field_type'] === 'daterange' && !empty($field_settings['end_operators'])) {
      $query->condition($field_settings['field'], $date->format(DateTimePlus::FORMAT), $operator);
      $query->condition($field_settings['field'] . '.end_value', $date->format(DateTimePlus::FORMAT), $field_settings['end_operators']);
    }
    else {
      $query->condition($field_settings['field'], $date->format(DateTimePlus::FORMAT), $operator);
    }
  }

  /**
   * Get fields date in array of options.
   *
   * @param array $fields_date
   *   This is array of fields date.
   *
   * @return array
   *   Return array of options.
   */
  public function fieldDateToOption(array $fields_date) {
    $options = [];

    foreach ($fields_date as $field) {
      $options[$field['key']] = $field['label'];
    }

    return $options;
  }
}
