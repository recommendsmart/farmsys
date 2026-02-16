<?php

namespace Drupal\friggeri_cv\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'friggeri_cv_profile_entity_box_default' field widget.
 *
 * @FieldWidget(
 *   id = "friggeri_cv_profile_entity_box_default",
 *   label = @Translation("Profile Entity Box Default"),
 *   field_types = {"friggeri_cv_profile_entity_box"},
 * )
 */
class ProfileEntityBoxDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $item = $items[$delta];
    $value = $item->toArray();

    $element += [
      '#type' => 'details',
      '#open' => FALSE,
    ];

    $element["tenure"] = [
      '#type' => "textfield",
      '#default_value' => $value["tenure"],
      '#title' => $this->t('Tenure'),
      '#placeholder' => $this->t('ex. 2019-2020'),
    ];

    $element["title"] = [
      '#type' => "textfield",
      '#default_value' => $value["title"],
      '#title' => $this->t('Title'),
      '#placeholder' => $this->t('ex. COVID-19 Tester'),
    ];

    $element["employer"] = [
      '#type' => "textfield",
      '#default_value' => $value["employer"],
      '#title' => $this->t('Employer'),
      '#placeholder' => $this->t('ex. World Health Organization (WHO)'),
    ];

    $element["domain"] = [
      '#type' => "textfield",
      '#default_value' => $value["domain"],
      '#title' => $this->t('Domain'),
      '#placeholder' => $this->t('ex. Domain: Medical'),
    ];

    $element["info"] = [
      '#type' => 'text_format',
      '#format' => 'full_html',
      '#title' => $this->t("Additional information"),
      '#default_value' => $value["info"],
      '#description' => $this->t('ex. I performed nasal and oral COVID-19 swab tests at testing sites, hospitals, nursing homes and offices.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      if (count($value['info'])) {
        $value['info'] = $value['info']['value'];
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $parents = $form['#parents'];

    // Determine the number of widgets to display.
    switch ($cardinality) {
      case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:
        $field_state = static::getWidgetState($parents, $field_name, $form_state);
        $max = $field_state['items_count'] - 1;
        $is_multiple = TRUE;
        break;

      default:
        $max = $cardinality - 1;
        $is_multiple = ($cardinality > 1);
        break;
    }

    $title = $this->fieldDefinition->getLabel();
    $description = $this->getFilteredDescription();

    $elements = [];

    for ($delta = 0; $delta <= $max; $delta++) {
      // Add a new empty item if it doesn't exist yet at this delta.
      if (!isset($items[$delta])) {
        $items->appendItem();
      }

      // For multiple fields, title and description are handled by the wrapping
      // table.
      if ($is_multiple) {
        $element = [
          '#title' => $this->t('Experience'),
          '#title_display' => 'invisible',
          '#description' => '',
        ];
      }
      else {
        $element = [
          '#title' => $title,
          '#title_display' => 'before',
          '#description' => $description,
        ];
      }

      $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

      if ($element) {
        // Input field for the delta (drag-n-drop reordering).
        if ($is_multiple) {
          // We name the element '_weight' to avoid clashing with elements
          // defined by widget.
          $element['_weight'] = [
            '#type' => 'weight',
            '#title' => $this->t('Weight for row @number', ['@number' => $delta + 1]),
            '#title_display' => 'invisible',
            // Note: this 'delta' is the FAPI #type 'weight' element's property.
            '#delta' => $max,
            '#default_value' => $items[$delta]->_weight ?: $delta,
            '#weight' => 100,
          ];
        }
        if ($items[$delta]->isEmpty()) {
          $element['#open'] = TRUE;
        }

        $elements[$delta] = $element;
      }
    }

    $elements += [
      '#theme' => 'field_multiple_value_form',
      '#field_name' => $field_name,
      '#cardinality' => $cardinality,
      '#cardinality_multiple' => $this->fieldDefinition->getFieldStorageDefinition()->isMultiple(),
      '#required' => $this->fieldDefinition->isRequired(),
      '#title' => $title,
      '#description' => $description,
      '#max_delta' => $max,
    ];

    // Add 'add more' button, if not working with a programmed form.
    if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && !$form_state->isProgrammed()) {
      $id_prefix = implode('-', array_merge($parents, [$field_name]));
      $wrapper_id = Html::getUniqueId($id_prefix . '-add-more-wrapper');
      $elements['#prefix'] = '<div id="' . $wrapper_id . '">';
      $elements['#suffix'] = '</div>';

      $elements['add_more'] = [
        '#type' => 'submit',
        '#name' => strtr($id_prefix, '-', '_') . '_add_more',
        '#value' => $this->t('Add experience'),
        '#attributes' => ['class' => ['field-add-more-submit']],
        '#limit_validation_errors' => [array_merge($parents, [$field_name])],
        '#submit' => [[static::class, 'addMoreSubmit']],
        '#ajax' => [
          'callback' => [static::class, 'addMoreAjax'],
          'wrapper' => $wrapper_id,
          'effect' => 'fade',
        ],
      ];
    }

    return $elements;
  }

}
