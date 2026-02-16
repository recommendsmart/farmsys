<?php

namespace Drupal\entity_reference_tree\Plugin\Field\FieldWidget;

use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;

/**
 * A entity reference tree widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_tree",
 *   label = @Translation("Entity reference tree widget"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class EntityReferenceTreeWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $arr_element = parent::formElement($items, $delta, $element, $form, $form_state);
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['library'][] = 'entity_reference_tree/widget';
    $arr_target = empty($arr_element['target_id']['#selection_settings']['target_bundles']) ? [] : $arr_element['target_id']['#selection_settings']['target_bundles'];
    $str_target_type = $arr_element['target_id']['#target_type'];
    // Target bundle of the entity tree.
    if (empty($arr_target)) {
      $str_target = '*';
    }
    else
    {
      $str_target = implode(',', $arr_target);
    }

    //The id of the autocomplete text field.
    //To ensure unqiueness when being used within Paragraph entities
    //add the ids of any parent elements as a prefix to the the
    //edit id.
    $parents = $element['#field_parents'];
    $id_prefix = '';
    if (!empty($parents)) {
      //Empty check necessary because implode will return the
      //separator when given an empty array.
      $id_prefix = str_replace('_', '-', implode('-', array_merge($parents))) . '-';
    }

    //Including the delta in the id to follow the Entity Reference module's convention.
    $edit_id = 'edit-' . $id_prefix . str_replace('_', '-', $items->getName()) . '-' . $delta . '-target-id';

    $arr_element['target_id']['#id'] = $edit_id;
    $arr_element['target_id']['#tags'] = TRUE;
    $arr_element['target_id']['#default_value'] = $items->referencedEntities();

    $label = $this->getSetting('label');
    if (!$label) {
      $label = $this->t('@label tree', [
        '@label' => ucfirst(str_replace('_', ' ', $str_target_type)),
      ]);
    }
    else {
      $label = $this->t('@label', ['@label' => $label]);
    }

    $dialog_title = $this->getSetting('dialog_title');
    if (empty($dialog_title)) {
      $dialog_title = $label;
    }
    else {
      $dialog_title = $this->t('@title', ['@title' => $dialog_title]);
    }

    $arr_element['dialog_link'] = [
      '#type' => 'link',
      '#title' => $label,
      '#url' => Url::fromRoute(
          'entity_reference_tree.widget_form',
          [
            'field_edit_id' => $edit_id,
            'bundle' => $str_target,
            'entity_type' => $str_target_type,
            'theme' => $this->getSetting('theme'),
            'dots' => $this->getSetting('dots'),
            'dialog_title' => $dialog_title,
            'limit' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
          ]),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'button',
        ],
      ],
    ];

    return $arr_element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return $values['target_id'];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        // JsTree theme
        'theme' => 'default',
        // Using dot line.
        'dots' => 0,
        // Button label.
        'label' => '',
        // Dialog title.
        'dialog_title' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    // JsTRee theme.
    $element['theme'] = [
        '#type' => 'radios',
        '#title' => t('JsTree theme'),
        '#default_value' => $this->getSetting('theme'),
        '#required' => TRUE,
        '#options' => array(
            'default' => $this
            ->t('Default'),
            'default-dark' => $this
            ->t('Default Dark'),
        ),
    ];
    // Tree dot.
    $element['dots'] = [
        '#type' => 'radios',
        '#title' => t('Dot line'),
        '#default_value' => $this->getSetting('dots'),
        '#options' => array(
            0 => $this
            ->t('No'),
            1 => $this
            ->t('Yes'),
        ),
    ];
    // Button label.
    $element['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button label'),
      '#default_value' => $this->getSetting('label'),
    ];

    $element['dialog_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dialog title'),
      '#default_value' => $this->getSetting('dialog_title'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // JsTree theme.
    $summary[] = t('JsTree theme: @theme', array('@theme' => $this->getSetting('theme')));
    // Button label.
    if ($label = $this->getSetting('label')) {
      $summary[] = t('Button label: @label', ['@label' => $label]);
    }
    // Dialog title.
    if ($label = $this->getSetting('dialog_title')) {
      $summary[] = t('Dialog title: @title', ['@title' => $label]);
    }
    
    return $summary;
  }
}
