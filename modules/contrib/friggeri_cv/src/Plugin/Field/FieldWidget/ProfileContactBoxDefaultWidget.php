<?php

namespace Drupal\friggeri_cv\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Defines the 'friggeri_cv_profile_contact_box_default' field widget.
 *
 * @FieldWidget(
 *   id = "friggeri_cv_profile_contact_box_default",
 *   label = @Translation("Profile Contact Box Default"),
 *   field_types = {"friggeri_cv_profile_contact_box"},
 * )
 */
class ProfileContactBoxDefaultWidget extends WidgetBase {

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

    $element["heading"] = [
      '#type' => "textfield",
      '#default_value' => $value["heading"],
      '#title' => $this->t('Heading'),
    ];

    $field_name = $items->getFieldDefinition()->getName();
    $icon_class = $form_state->getValues()[$field_name][$delta]['font_awesome_icon'] ?? $value["font_awesome_icon"];
    $element["font_awesome_icon"] = [
      '#type' => "select",
      '#options' => $this->getOptions(),
      '#default_value' => $value["font_awesome_icon"],
      '#title' => $this->t("Icon"),
      '#prefix' => '<div id="font-awesome-icon__' . $delta . '" class="fa-icon"><i class="' . $icon_class . '"></i>',
      '#suffix' => '</div>',
      '#attached' => [
        'library' => [
          'friggeri_cv/font-awesome',
          'friggeri_cv/font-awesome.select',
        ],
      ],
      '#attributes' => [
        'class' => ['fa-select'],
      ],
      '#ajax' => [
        'callback' => [$this, 'ajaxFontAwesomeIcon'],
        'event' => 'change',
      ],
    ];

    $element["contacts"] = [
      '#type' => 'text_format',
      '#format' => 'full_html',
      '#title' => $this->t("Contact Details"),
      '#default_value' => $value["contacts"],
    ];

    return $element;
  }

  /**
   * Ajax callback for font_awesome_icon select field.
   *
   * @param array $form
   *   The form structure where widgets are being attached to.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The selected font awesome icon AjaxResponse.
   */
  public function ajaxFontAwesomeIcon(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $delta = $trigger['#parents'][1];
    $selector = '#font-awesome-icon__' . $delta . ' > i';
    $class = $trigger['#value'];

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand($selector, '<i class="' . $class . '"></i>'));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      if (count($value['contacts'])) {
        $value['contacts'] = $value['contacts']['value'];
      }
    }

    return $values;
  }

  /**
   * Get the options of the font_awesome_icon select field.
   *
   * @return array
   *   Select options.
   */
  private function getOptions() {
    // @see https://raw.githubusercontent.com/FortAwesome/Font-Awesome/fa-4/src/icons.yml
    $url = drupal_get_path("module", "friggeri_cv") . "/img/icons/icons.yml";
    $icons = Yaml::parse(file_get_contents($url))["icons"];
    $options = ["" => $this->t("-- Select --")];
    foreach ($icons as $icon) {
      $options["fa fa-" . $icon["id"]] = $icon["name"];
    }

    return $options;
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
          '#title' => $this->t('Contact'),
          '#title_display' => 'invisible',
          '#description' => '',
        ];
      }
      else {
        $element = [
          '#title' => $this->t('Contact'),
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
        '#value' => $this->t('Add contact'),
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
