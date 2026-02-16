<?php

namespace Drupal\designs\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\designs\DesignRegion;

/**
 * Provides the region plugin form.
 */
class RegionForm extends FormBase {

  /**
   * The design region.
   *
   * @var \Drupal\designs\DesignRegion
   */
  protected $region;

  /**
   * Get the region.
   *
   * @return \Drupal\designs\DesignRegion
   *   The region.
   */
  public function getRegion(): DesignRegion {
    return $this->region;
  }

  /**
   * Set the region.
   *
   * @param \Drupal\designs\DesignRegion $region
   *   The region.
   *
   * @return $this
   *   The object instance.
   */
  public function setRegion(DesignRegion $region) {
    $this->region = $region;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTitle() {
    $definition = $this->region->getDefinition();
    return $this->t('Region: @label', [
      '@label' => $definition['label'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $wrapper_id = self::getElementId($form['#parents']);
    $form['#prefix'] = '<div id="' . $wrapper_id . '">';
    $form['#suffix'] = '</div>';

    // Get the names of all the sources.
    $sources = $this->design->getSources();

    $weight_class = self::getElementId($form['#parents'], '-weight');

    // Store the contents.
    $form['contents'] = [
      '#type' => 'table',
      '#description' => $this->t('Content will be displayed in this design region.'),
      '#header' => [
        $this->getTitle(),
        $this->t('Remove'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('No content added.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => $weight_class,
        ],
      ],
    ];

    $contents = $this->region->getSources();
    // Some locations may not have run the element validation before rebuilding
    // the values, so filter accordingly.
    if (isset($contents['contents']) && is_array($contents['contents'])) {
      uasort($contents['contents'], [SortArray::class, 'sortByWeightElement']);
      $contents = array_map(function ($item) {
        return $item['operation']['value'];
      }, $contents['contents']);
    }

    // Place already existing items into the table rows.
    $weight = 0;
    $max_weight = \count($contents);

    // Generate the rows matching the content order.
    foreach ($contents as $index => $item) {
      if (!isset($sources[$item])) {
        continue;
      }
      $form['contents'][$index] = [
        '#parents' => array_merge($form['#parents'], ['contents', $index]),
        '#attributes' => [
          'class' => ['draggable'],
        ],
        '#weight' => $weight,
      ];
      $form['contents'][$index]['label'] = [
        '#plain_text' => $sources[$item],
      ];
      $form['contents'][$index]['operation'] = [
        '#type' => 'container',
        'value' => [
          '#type' => 'hidden',
          '#value' => $item,
        ],
        'remove' => [
          '#type' => 'submit',
          '#op' => 'remove_region',
          '#value' => $this->t('Remove'),
          '#name' => static::getElementId($form['contents'][$index]['#parents'], '-remove'),
          '#submit' => self::getSubmit($form),
          '#index' => $index,
          '#ajax' => self::getAjax($form),
          '#design_parents' => $form['#design_parents'],
        ],
      ];
      $form['contents'][$index]['weight'] = [
        '#type' => 'weight',
        '#default_value' => $weight,
        '#delta' => $max_weight,
        '#title' => $this->t('Weight'),
        '#title_display' => 'invisible',
        '#attributes' => [
          'class' => [$weight_class],
        ],
      ];

      $weight++;
    }

    $addition_name = self::getElementId($form['#parents'], '-addition');
    $form['__addition__'] = [
      '#type' => 'inline_container',
      'field' => [
        '#type' => 'select',
        '#options' => [
          '' => $this->t('- Select -'),
        ] + $sources,
        '#name' => "{$addition_name}-field",
        '#parents' => ["{$addition_name}-field"],
      ],
      'submit' => [
        '#type' => 'submit',
        '#op' => 'add_region',
        '#value' => $this->t('Add content'),
        '#submit' => self::getSubmit($form),
        '#name' => "{$addition_name}-submit",
        '#ajax' => self::getAjax($form),
        '#design_parents' => $form['#design_parents'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function massageFormValues(array $form, FormStateInterface $form_state) {
    $completed = $form_state->getUserInput();
    $values = $form_state->getValue($form['#parents']);

    // Converts this region into simple content references.
    $result = [];
    if (!empty($values['contents'])) {
      uasort($values['contents'], [SortArray::class, 'sortByWeightElement']);
      $result = array_values(array_map(function ($item) {
        return $item['operation']['value'];
      }, $values['contents']));
      NestedArray::setValue($completed, $form['#parents'], $result);
    }

    $form_state->setValue($form['#parents'], $result);

    // Clear addition selection.
    $addition_name = self::getElementId($form['#parents'], '-addition');
    unset($completed["{$addition_name}-field"]);
    $form_state->setUserInput($completed);
  }

  /**
   * {@inheritdoc}
   */
  public static function multistepAjax(array $form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    switch ($trigger_element['#op']) {
      case 'add_region':
        $target = array_slice($trigger_element['#array_parents'], 0, -2);
        return NestedArray::getValue($form, $target);

      case 'remove_region':
        $target = array_slice($trigger_element['#array_parents'], 0, -4);
        return NestedArray::getValue($form, $target);
    }

    return parent::multistepAjax($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function multistepSubmit(array $form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    switch ($trigger_element['#op']) {
      case 'add_region':
        $parents = array_slice($trigger_element['#parents'], 0, -2);

        // Add the content item.
        $addition_name = self::getElementId($parents, '-addition');

        $values = $form_state->getValue($parents);
        $values[] = $form_state->getValue(["{$addition_name}-field"]);
        $form_state->setValue($parents, $values);

        // Rebuild the form.
        $form_state->setRebuild();
        break;

      case 'remove_region':
        $parents = array_slice($trigger_element['#parents'], 0, -4);

        // Get the region from the trigger element.
        $index = $trigger_element['#index'];

        // Get the values and additions.
        $values = $form_state->getValue($parents);

        unset($values[$index]);
        $values = array_values($values);
        $form_state->setValue($parents, $values);

        $form_state->setRebuild();
        break;

      default:
        parent::multistepSubmit($form, $form_state);
    }
  }

}
