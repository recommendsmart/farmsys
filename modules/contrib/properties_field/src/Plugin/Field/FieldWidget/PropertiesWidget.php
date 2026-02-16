<?php

namespace Drupal\properties_field\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\MachineName;
use Drupal\properties_field\PropertiesValueType\PropertiesValueTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Provides the default widget for the properties field.
 *
 * @FieldWidget(
 *   id = "properties_default",
 *   label = @Translation("Properties"),
 *   field_types = {
 *     "properties",
 *   },
 *   multiple_values = TRUE
 * )
 */
class PropertiesWidget extends WidgetBase {

  /**
   * Plugin manager for the properties value types.
   *
   * @var \Drupal\properties_field\PropertiesValueType\PropertiesValueTypeManager
   */
  protected $valueTypeManager;

  /**
   * The properties value type options.
   *
   * @var array
   */
  protected $valueTypeOptions;

  /**
   * The properties value type plugins.
   *
   * @var \Drupal\properties_field\PropertiesValueType\PropertiesValueTypeInterface[]
   */
  protected $valueTypePlugins = [];

  /**
   * Class constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\properties_field\PropertiesValueType\PropertiesValueTypeManager $properties_value_type_manager
   *   Plugin manager for the properties value types.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, PropertiesValueTypeManager $properties_value_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->valueTypeManager = $properties_value_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.properties_value_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'value_types' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['value_types'] = [];

    foreach ($this->getValueTypePlugins() as $plugin_id => $plugin) {
      $element_in = [
        '#type' => 'details',
        '#title' => $plugin->getPluginDefinition()['label'],
        '#open' => FALSE,
      ];

      $element = $plugin->widgetSettingsForm($element_in, $form_state, $form);

      if ($element !== $element_in) {
        $form['value_types'][$plugin_id] = $element;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $configurations = $this->getSetting('value_types');

    foreach ($configurations as $plugin_id => $configuration) {
      if (!$configuration) {
        continue;
      }

      if (!$plugin = $this->getValueTypePlugin($plugin_id)) {
        continue;
      }

      foreach ($plugin->widgetSettingsSummary() as $plugin_summary) {
        $summary[] = $this->t('<strong>@plugin:</strong> @summary', [
          '@plugin' => $plugin->getPluginDefinition()['label'],
          '@summary' => $plugin_summary,
        ]);
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $parents = $element['#field_parents'];
    $field_name = $this->fieldDefinition->getName();
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $items_count = $field_state['items_count'] ?: 1;

    $widget_parents = array_merge($parents, [$field_name]);
    $id_prefix = implode('-', $widget_parents);
    $name_prefix = str_replace('-', '_', $id_prefix);
    $wrapper_id = Html::getUniqueId($id_prefix . '-wrapper');

    // Wrap the whole widget.
    $element['#prefix'] = '<div id="' . $wrapper_id . '">';
    $element['#suffix'] = '</div>';

    // Build the table.
    $element['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Machine name'),
        $this->t('Type'),
        $this->t('Value'),
        $this->t('Weight'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'table-sort-weight',
        ],
      ],
    ];

    if ($items_count > 1) {
      $element['table']['#header'][] = '';
    }


    $required = $element['#required'];
    $set_defaults = !isset($field_state['item_types']);

    for ($delta = 0; $delta < $items_count; $delta++) {
      $item = new \stdClass();
      if ($set_defaults && isset($items[$delta])) {
        $item = $items[$delta];
      }

      $element['table'][$delta]['#attributes']['class'][] = 'draggable';

      $element['table'][$delta]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#title_display' => 'invisible',
        '#default_value' => $item->label ?? NULL,
        '#autocomplete_route_name' => 'properties_field.label_autocomplete',
        '#autocomplete_route_parameters' => [
          'entity_type_id' => $items->getEntity()->getEntityTypeId(),
          'bundle' => $items->getEntity()->bundle(),
          'field_name' => $this->fieldDefinition->getName(),
          'entity_id' => $items->getEntity()->id() ?? '0',
        ],
        '#maxlength' => 100,
        '#required' => $required,
        '#attributes' => [
          'class' => ['properties-label'],
        ],
        '#attached' => [
          'library' => [
            'properties_field/label',
          ],
        ],
      ];

      $element['table'][$delta]['machine_name'] = [
        '#type' => 'machine_name',
        '#title' => $this->t('Machine name'),
        '#title_display' => 'invisible',
        '#default_value' => $item->machine_name ?? NULL,
        '#process' => [
          [static::class, 'processMachineName'],
          [MachineName::class, 'processMachineName'],
          [MachineName::class, 'processAutocomplete'],
          [MachineName::class, 'processAjaxForm'],
        ],
        '#machine_name' => [
          'exists' => [static::class, 'machineNameExists'],
          'label' => NULL,
          'standalone' => TRUE,
        ],
        '#required' => $required,
      ];

      // Populate the type.
      if (!isset($field_state['item_types'][$delta])) {
        $field_state['item_types'][$delta] = $item->type ?? '';
      }

      // Generate the wrapper ID.
      $value_wrapper_id = Html::getUniqueId($id_prefix . '-value');

      // Parents array of the type element.
      $type_parents = array_merge($widget_parents, ['table', $delta, 'type']);

      $element['table'][$delta]['type']['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Type'),
        '#title_display' => 'invisible',
        '#options' => $this->getValueTypeOptions(),
        '#empty_option' => '- ' . $this->t('Select') . ' -',
        '#empty_value' => '',
        '#default_value' => $field_state['item_types'][$delta],
        '#element_validate' => [
          [static::class, 'validateType'],
        ],
        '#parents' => $type_parents,
        '#required' => $required,
        '#ajax' => [
          'event' => 'change',
          'callback' => '',
          'wrapper' => $value_wrapper_id,
          'method' => 'html',
          'disable-refocus' => TRUE,
          'trigger_as' => [
            'name' => $name_prefix . '_select_type_' . $delta,
            'value' => $this->t('Select'),
          ],
        ],
      ];

      $element['table'][$delta]['type']['submit'] = [
        '#type' => 'submit',
        '#name' => $name_prefix . '_select_type_' . $delta,
        '#value' => $this->t('Select'),
        '#limit_validation_errors' => [$type_parents],
        '#submit' => [
          [static::class, 'selectTypeSubmit'],
        ],
        '#parents' => array_merge($widget_parents, ['type']),
        '#attributes' => [
          'class' => ['js-hide'],
        ],
        '#ajax' => [
          'callback' => [static::class, 'selectTypeAjax'],
          'wrapper' => '',
        ],
      ];

      $plugin = $this->getValueTypePlugin($field_state['item_types'][$delta]);
      $value_element = [];

      if ($plugin) {
        $value_element = [
          '#title' => $this->t('Value'),
          '#title_display' => 'invisible',
          '#required' => $required,
        ];

        $value_element = $plugin->widgetForm(
          $value_element,
          $item->value ?? NULL,
          $form_state
        );
      }

      $value_element['#prefix'] = '<div id="' . $value_wrapper_id . '">';
      $value_element['#suffix'] = '</div>';

      $element['table'][$delta]['value'] = $value_element;

      $element['table'][$delta]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight'),
        '#title_display' => 'invisible',
        '#default_value' => $delta,
        '#required' => $required,
        '#attributes' => [
          'class' => [
            'table-sort-weight',
          ],
        ],
      ];

      // Add the remove button if there's more than one item.
      if ($items_count > 1) {
        $element['table'][$delta]['remove'] = [
          '#type' => 'submit',
          '#name' => $name_prefix . '_remove_' . $delta,
          '#value' => $this->t('Remove'),
          '#submit' => [
            [static::class, 'removeItemSubmit'],
          ],
          '#limit_validation_errors' => [],
          '#parents' => array_merge($widget_parents, ['remove']),
          '#ajax' => [
            'callback' => [static::class, 'removeItemAjax'],
            'wrapper' => $wrapper_id,
            'effect' => 'fade',
          ],
        ];
      }

      $required = FALSE;
    }

    // Update the field state.
    static::setWidgetState($parents, $field_name, $form_state, $field_state);

    // Add the add more button.
    $element['add_more'] = [
      '#type' => 'submit',
      '#name' => $name_prefix . '_add_more',
      '#value' => $this->t('Add another item'),
      '#submit' => [
        [static::class, 'addMoreSubmit'],
      ],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => [static::class, 'addMoreAjax'],
        'wrapper' => $wrapper_id,
        'effect' => 'fade',
      ],
    ];

    // Set the cardinality and max delta, addMoreAjax() depends on them.
    $element['#cardinality'] = FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED;
    $element['#max_delta'] = 'table';

    return $element;
  }

  /**
   * Get the properties value type options.
   *
   * @return array
   *   The properties value type options.
   */
  protected function getValueTypeOptions() {
    if (isset($this->valueTypeOptions)) {
      return $this->valueTypeOptions;
    }

    $this->valueTypeOptions = [];
    foreach ($this->valueTypeManager->getDefinitions() as $plugin_id => $definition) {
      $this->valueTypeOptions[$plugin_id] = $definition['label'];
    }

    return $this->valueTypeOptions;
  }

  /**
   * Get all properties value type plugins.
   *
   * @return \Drupal\properties_field\PropertiesValueType\PropertiesValueTypeInterface[]
   *   All properties value type plugins keyed by plugin ID.
   */
  protected function getValueTypePlugins() {
    $plugins = [];
    foreach ($this->valueTypeManager->getDefinitions() as $plugin_id => $definition) {
      $plugins[$plugin_id] = $this->getValueTypePlugin($plugin_id);
    }

    return $plugins;
  }

  /**
   * Get the properties value type plugin.
   *
   * @param string $plugin_id
   *   The properties value type plugin ID.
   *
   * @return \Drupal\properties_field\PropertiesValueType\PropertiesValueTypeInterface|null
   *   The properties value type plugin or NULL if it doesnt exist.
   */
  protected function getValueTypePlugin($plugin_id) {
    if (isset($this->valueTypePlugins[$plugin_id])) {
      return $this->valueTypePlugins[$plugin_id];
    }

    if (!$this->valueTypeManager->hasDefinition($plugin_id)) {
      return NULL;
    }

    /** @var \Drupal\properties_field\PropertiesValueType\PropertiesValueTypeInterface $plugin */
    $plugin = $this->valueTypeManager->createInstance($plugin_id);
    $this->valueTypePlugins[$plugin_id] = $plugin;

    return $this->valueTypePlugins[$plugin_id];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);
    $values = $values['table'];

    if (!$values) {
      return [];
    }

    usort($values, SortArray::class . '::sortByWeightElement');

    foreach ($values as &$value) {
      unset($value['weight']);
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    $property_path = explode('.', $error->getPropertyPath());
    array_unshift($property_path, 'table');

    return NestedArray::getValue($element, $property_path);
  }

  /**
   * Validate a the type element.
   *
   * @param array $element
   *   The element being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateType(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Skip the validation when changing the type.
    $button = $form_state->getTriggeringElement();

    $array_parents = array_slice($element['#array_parents'], 0, -1);
    $array_parents[] = 'submit';

    if ($button['#array_parents'] === $array_parents) {
      return;
    }

    // Get the widget and delta.
    $widget = NestedArray::getValue($complete_form, array_slice($array_parents, 0, -4));
    $delta = $array_parents[count($array_parents) - 3];

    // Compare the type with the stored type.
    $field_state = static::getWidgetState($widget['#field_parents'], $widget['#field_name'], $form_state);

    if ($element['#value'] !== $field_state['item_types'][$delta]) {
      $form_state->setError($element, t('The value type has changed, you must confirm the new type first.'));
    }
  }

  /**
   * Form submit callback to change the value type of an item.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function selectTypeSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $parents = array_slice($button['#parents'], 0, -1);
    $array_parents = $button['#array_parents'];

    // Get the widget and delta to be updated.
    $widget = NestedArray::getValue($form, array_slice($array_parents, 0, -4));
    $delta = $array_parents[count($array_parents) - 3];

    // Update the field state.
    $type_parents = array_merge($parents, ['table', $delta, 'type']);
    $field_state = static::getWidgetState($widget['#field_parents'], $widget['#field_name'], $form_state);
    $field_state['item_types'][$delta] = $form_state->getValue($type_parents);
    static::setWidgetState($widget['#field_parents'], $widget['#field_name'], $form_state, $field_state);

    // Remove the user input.
    $input_parents = array_merge($parents, ['table', $delta, 'value']);
    $user_input = &$form_state->getUserInput();
    NestedArray::unsetValue($user_input, $input_parents);

    $form_state->setRebuild();
  }

  /**
   * Ajax callback to return the value element when its type changed.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The value element to be rendered.
   */
  public static function selectTypeAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $array_parents = array_slice($button['#array_parents'], 0, -2);
    $array_parents[] = 'value';

    $element = NestedArray::getValue($form, $array_parents);
    unset($element['#prefix'], $element['#suffix']);

    return $element;
  }

  /**
   * Form submit callback to remove an item.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function removeItemSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $array_parents = $button['#array_parents'];

    // Get the widget and to be removed delta.
    $widget = NestedArray::getValue($form, array_slice($array_parents, 0, -3));
    $delta = $array_parents[count($array_parents) - 2];

    // Update the field state.
    $field_state = static::getWidgetState($widget['#field_parents'], $widget['#field_name'], $form_state);
    $field_state['items_count']--;
    unset($field_state['item_types'][$delta]);
    $field_state['item_types'] = array_values($field_state['item_types']);
    static::setWidgetState($widget['#field_parents'], $widget['#field_name'], $form_state, $field_state);

    // Remove the user input and sort by weight.
    $user_input = &$form_state->getUserInput();
    $values = &NestedArray::getValue($user_input, $widget['#parents']);

    if (isset($values['table'][$delta])) {
      unset($values['table'][$delta]);
      $values['table'] = array_values($values['table']);
    }

    $form_state->setRebuild();
  }

  /**
   * Ajax callback to return the widget when an item was removed.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The widget to be rendered.
   */
  public static function removeItemAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    return NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -3));
  }

  /**
   * Set the source of a machine name element.
   *
   * @param array $element
   *   The element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The altered form element.
   */
  public static function processMachineName(array $element, FormStateInterface $form_state, array $complete_form) {
    $source = $element['#array_parents'];
    array_pop($source);
    $source[] = 'label';

    $element['#machine_name']['source'] = $source;

    return $element;
  }

  /**
   * Dummy machine name exists callback.
   *
   * @param string $machine_name
   *   The submitted machine name.
   * @param array $element
   *   The machine name form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   Always FALSE to disable the validation.
   */
  public static function machineNameExists($machine_name, array $element, FormStateInterface $form_state) {
    return FALSE;
  }

}
