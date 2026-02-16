<?php

namespace Drupal\ingredient\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ingredient\Utility\IngredientUnitUtility;
use Drupal\ingredient\Utility\IngredientQuantityUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ingredient_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "ingredient_autocomplete",
 *   module = "ingredient",
 *   label = @Translation("Autocomplete ingredient widget"),
 *   field_types = {
 *     "ingredient"
 *   }
 * )
 */
class IngredientWidget extends WidgetBase {

  /**
   * The ingredient.unit service.
   *
   * @var \Drupal\ingredient\Utility\IngredientUnitUtility
   */
  protected $ingredientUnitUtility;

  /**
   * The ingredient.quantity service.
   *
   * @var \Drupal\ingredient\Utility\IngredientQuantityUtility
   */
  protected $ingredientQuantityUtility;

  /**
   * Constructs a IngredientWidget.
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
   * @param \Drupal\ingredient\Utility\IngredientUnitUtility $ingredient_unit_utility
   *   The ingredient.unit service.
   * @param \Drupal\ingredient\Utility\IngredientQuantityUtility $ingredient_quantity_utility
   *   The ingredient.quantity service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, IngredientUnitUtility $ingredient_unit_utility, IngredientQuantityUtility $ingredient_quantity_utility) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->ingredientUnitUtility = $ingredient_unit_utility;
    $this->ingredientQuantityUtility = $ingredient_quantity_utility;
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
      $container->get('ingredient.unit'),
      $container->get('ingredient.quantity')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $referenced_entities = $items->referencedEntities();

    // Get the enabled units and sort them for the select options.
    $units = $this->ingredientUnitUtility->getConfiguredUnits($this->getFieldSetting('unit_sets'));
    $units = $this->ingredientUnitUtility->sortUnitsByName($units);

    // Strange, but html_entity_decode() doesn't handle &frasl;.
    $quantity = isset($items[$delta]->quantity) ? preg_replace('/\&frasl;/', '/', $this->ingredientQuantityUtility->getQuantityFromDecimal($items[$delta]->quantity, '{%d} %d&frasl;%d', TRUE)) : '';
    $element['quantity'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Quantity'),
      '#default_value' => $quantity,
      '#size' => 8,
      '#maxlength' => 8,
      '#attributes' => ['class' => ['recipe-ingredient-quantity']],
    ];
    $element['unit_key'] = [
      '#type' => 'select',
      '#title' => $this->t('Unit'),
      '#default_value' => isset($items[$delta]->unit_key) ? $items[$delta]->unit_key : $this->getFieldSetting('default_unit'),
      '#options' => $this->ingredientUnitUtility->createUnitSelectOptions($units),
      '#attributes' => ['class' => ['recipe-ingredient-unit-key']],
    ];
    $element['target_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Name'),
      '#target_type' => 'ingredient',
      '#autocreate' => [
        'bundle' => 'ingredient',
      ],
      // Entity reference field items are handling validation themselves via
      // the 'ValidReference' constraint.
      '#validate_reference' => FALSE,
      '#default_value' => isset($referenced_entities[$delta]) ? $referenced_entities[$delta] : NULL,
      '#size' => 25,
      '#maxlength' => 128,
      '#attributes' => ['class' => ['recipe-ingredient-name']],
    ];
    $element['note'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Note'),
      '#default_value' => isset($items[$delta]->note) ? $items[$delta]->note : '',
      '#size' => 40,
      '#maxlength' => 255,
      '#attributes' => ['class' => ['recipe-ingredient-note']],
    ];
    $element['#element_validate'] = [[$this, 'validate']];
    $element['#attached']['library'][] = 'ingredient/drupal.ingredient';

    return $element;
  }

  /**
   * Validate the ingredient field.
   *
   * @param array $element
   *   The ingredient field's form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validate(array $element, FormStateInterface $form_state) {
    if (empty($element['unit_key']['#value']) && !empty($element['name']['#value'])) {
      $form_state->setError($element['unit_key'], $this->t('You must choose a valid unit.'));
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $value) {
      // Convert fractional quantities to decimal.
      $values[$key]['quantity'] = round($this->ingredientQuantityUtility->getQuantityFromFraction($value['quantity']), 6);

      // The entity_autocomplete form element returns an array when an entity
      // was "autocreated", so we need to move it up a level.
      if (is_array($value['target_id'])) {
        unset($values[$key]['target_id']);
        $values[$key] += $value['target_id'];
      }
    }

    return $values;
  }

}
