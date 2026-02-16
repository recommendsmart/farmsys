<?php

namespace Drupal\aggrid\Plugin\Field\FieldWidget;

use Drupal\aggrid\Entity\Aggrid;
use Drupal\aggrid\Entity;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

/**
 * Plugin implementation of the 'aggrid-json' widget.
 *
 * @FieldWidget(
 *   id = "aggrid_json_widget_type",
 *   label = @Translation("ag-Grid JSON edit mode"),
 *   field_types = {
 *     "aggrid"
 *   }
 * )
 */
class AggridJsonWidgetType extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    /*\\
    $summary[] = t('Textfield size: @size', ['@size' => $this->getSetting('size')]);
    if (!empty($this->getSetting('placeholder'))) {
    $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $this->getSetting('placeholder')]);
    }
     */
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $config = \Drupal::config('aggrid.general');

    $field_name = $this->fieldDefinition->getName();

    $item_id = Html::getUniqueId("ht-$field_name-$delta");

    if ((isset($form['#parents'][0]) && $form['#parents'][0] == 'default_value_input') || empty($items[$delta]->aggrid_id)) {

      $options = [];

      $aggridEntities = \Drupal::entityTypeManager()->getStorage('aggrid')->loadMultiple();

      foreach ($aggridEntities as $aggridEntity) {
        $options[$aggridEntity->id()] = $aggridEntity->label();
      }
  
      $element['aggrid_id'] = [
        '#type' => 'select',
        '#empty_option' => ' - ' . $this->t('Select') . ' - ',
        '#options' => $options,
        '#title' => $this->fieldDefinition->label() . ' - ' . $this->t('ag-Grid Config Entity'),
        '#description' => $this->t('Choose an ag-Grid Config Entity. *Once saved, this cannot be modified.'),
        '#default_value' => isset($items[$delta]->aggrid_id) ? $items[$delta]->aggrid_id : NULL,
      ];
      
    }
    else {

      $aggridEntity = Aggrid::load($items[$delta]->aggrid_id);
      $aggridDefault = json_decode($aggridEntity->get('aggridDefault'));

      if (empty($items[$delta]->value) || $items[$delta]->value == '{}') {
        $aggridValue = json_encode($aggridDefault->rowData);
      }
      else {
        $aggridValue = $items[$delta]->value;
      }
  
      $element['aggrid_id'] = [
        '#type' => 'hidden',
        '#default_value' => isset($items[$delta]->aggrid_id) ? $items[$delta]->aggrid_id : NULL,
      ];
  
      $element['value'] = [
        '#type' => 'textarea',
        '#attributes' => [
          'class' => ['aggrid-json-widget'],
          'id' => [$item_id . '_rowData'],
        ],
        '#attached' => [
          'library' => [
            'aggrid/aggrid.json.widget',
          ],
        ],
        '#value' => Xss::filter($aggridValue),
      ];

    }

    return $element;
  }

}
