<?php

declare(strict_types = 1);

namespace Drupal\entity_version\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_version' widget.
 *
 * @FieldWidget(
 *   id = "entity_version",
 *   label = @Translation("Entity version"),
 *   field_types = {
 *     "entity_version"
 *   }
 * )
 */
class EntityVersionWidget extends WidgetBase implements WidgetInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['version'] = [
      '#type' => 'details',
      '#title' => $this->t('Version'),
    ] + $element;

    $element['version']['major'] = [
      '#type' => 'number',
      '#title' => $this->t('Major'),
      '#default_value' => $items[$delta]->major ?? NULL,
      '#size' => 5,
      '#min' => 0,
      '#step' => 1,
      '#required' => FALSE,
    ];

    $element['version']['minor'] = [
      '#type' => 'number',
      '#title' => $this->t('Minor'),
      '#default_value' => $items[$delta]->minor ?? NULL,
      '#size' => 5,
      '#min' => 0,
      '#step' => 1,
      '#required' => FALSE,
    ];

    $element['version']['patch'] = [
      '#type' => 'number',
      '#title' => $this->t('Patch'),
      '#default_value' => $items[$delta]->patch ?? NULL,
      '#size' => 5,
      '#min' => 0,
      '#step' => 1,
      '#required' => FALSE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      $version = $item['version'];
      $item = [
        'major' => $version['major'],
        'minor' => $version['minor'],
        'patch' => $version['patch'],
      ];
    }

    return $values;
  }

}
