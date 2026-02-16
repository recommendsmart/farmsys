<?php

namespace Drupal\designs_entity;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\designs\DesignContentManagerInterface;
use Drupal\designs\DesignInterface;
use Drupal\designs\DesignManagerInterface;
use Drupal\designs\DesignSettingManagerInterface;
use Drupal\designs\Form\PluginForm;

/**
 * Provides the modification to the Field UI to support entity design.
 */
class DesignsEntityFieldUi {

  use StringTranslationTrait;

  /**
   * The design manager.
   *
   * @var \Drupal\designs\DesignManagerInterface
   */
  protected $manager;

  /**
   * The design setting manager.
   *
   * @var \Drupal\designs\DesignSettingManagerInterface
   */
  protected $settingManager;

  /**
   * The design content manager.
   *
   * @var \Drupal\designs\DesignContentManagerInterface
   */
  protected $contentManager;

  /**
   * The design.
   *
   * @var \Drupal\designs\DesignInterface
   */
  protected $design;

  /**
   * The design source.
   *
   * @var \Drupal\designs\DesignSourceInterface
   */
  protected $source;

  /**
   * DesignFieldUIHandler constructor.
   *
   * @param \Drupal\designs\DesignManagerInterface $manager
   *   The design plugin manager.
   * @param \Drupal\designs\DesignSettingManagerInterface $settingManager
   *   The design setting manager.
   * @param \Drupal\designs\DesignContentManagerInterface $contentManager
   *   The design content manager.
   */
  public function __construct(
    DesignManagerInterface        $manager,
    DesignSettingManagerInterface $settingManager,
    DesignContentManagerInterface $contentManager
  ) {
    $this->manager = $manager;
    $this->settingManager = $settingManager;
    $this->contentManager = $contentManager;
  }

  /**
   * Processes the form based on the selection.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function displayFormAlter(array &$form, FormStateInterface $form_state) {
    // Nothing to provide design.
    if (empty($form['#fields']) && empty($form['#extra'])) {
      return;
    }

    // Get the callback object.
    $entity_display_form = $form_state->getBuildInfo()['callback_object'];

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $entity */
    $entity = $entity_display_form->getEntity();
    $config = $entity->getThirdPartySettings('designs_entity');

    $entity_form = new PluginForm(
      $this->manager,
      $this->settingManager,
      $this->contentManager,
      $config['design'] ?? '',
      $config,
      'entity',
      [
        'type' => $entity->getTargetEntityTypeId(),
        'bundle' => $entity->getTargetBundle(),
        'form' => $entity instanceof EntityFormDisplayInterface,
      ],
    );

    // Perform ajax.
    $ajax = [
      'callback' => [static::class, 'multistepAjax'],
      'wrapper' => $form['#id'],
      'effect' => 'fade',
    ];

    // Generate the design form.
    $form['design'] = [
      '#parents' => ['design'],
      '#contents_wrapper' => [],
      '#submit' => [[static::class, 'multistepSubmit']],
      '#design_ajax' => $ajax,
    ];
    $form['design'] = $entity_form->buildForm($form['design'], $form_state);
    $form['design']['#title'] = $this->t('Design settings');
    $form['design']['#open'] = TRUE;

    // Get the design from the design form.
    /** @var \Drupal\designs\DesignInterface|null $design */
    $design = $form['design']['#design'] ?? NULL;

    // Get reference to the form fields.
    $table = &$form['fields'];

    // Add in the design form.
    if ($design) {
      // Add the custom labels for display.
      foreach ($design->getSources() as $field_id => $label) {
        // Skip the table rendering for the custom content.
        if (isset($table[$field_id])) {
          continue;
        }
        $display_options = $entity->getComponent($field_id);
        if (!$display_options) {
          $display_options = [
            'region' => 'hidden',
            'weight' => 0,
          ];
        }

        $table[$field_id] = $this->buildExtraFieldRow(
          $field_id,
          $display_options,
          ['label' => $label],
          $this->getRegionLabels($design),
        );
        $form['#extra'][] = $field_id;
      }

      $region_options = $this->getRegionLabels($design);
      $table['#regions'] = $this->getRegions($design);
      foreach (Element::children($table) as $name) {
        $table[$name]['parent_wrapper']['parent']['#options'] = $region_options;
        $table[$name]['region']['#options'] = $region_options;
        $table[$name]['#region_callback'] = 'designs_entity_form_entity_view_display_row_region';
      }
    }

    // Manage the layout builder application behaviour.
    if (!empty($form['layout'])) {
      $form['layout']['#states']['invisible'][':input[name="design[design]"]'] = [
        'filled' => TRUE,
      ];
      $form['design']['#states']['invisible'][':input[name="layout[enabled]"]'] = [
        'checked' => TRUE,
      ];
    }

    $form['actions']['submit']['#validate'][] = 'designs_entity_form_entity_view_display_form_validate';
    array_unshift($form['actions']['submit']['#submit'], 'designs_entity_form_entity_view_display_form_submit');
  }

  /**
   * Validation for designs_entity_field_ui_display_form_alter().
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    $design_form = $form['design']['#form_handler'];
    $design_form->validateForm($form['design'], $form_state);
  }

  /**
   * Submission for designs_entity_field_ui_display_form_alter().
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\designs\Form\PluginForm $design_form */
    $design_form = $form['design']['#form_handler'];
    $design_form->submitForm($form['design'], $form_state);

    $regions = [];
    $design = $form['design']['#design'];
    if ($design) {
      $regions = $design->getPluginDefinition()->getRegionLabels();
    }

    $values = $form_state->getValue('design');

    // Build the regions using component weighting.
    $entity_display_form = $form_state->getBuildInfo()['callback_object'];
    $entity = $entity_display_form->getEntity();
    $values['regions'] = [];
    foreach ($entity->getComponents() as $key => $component) {
      $region = $component['region'];
      if (isset($regions[$region])) {
        $values['regions'][$region][$key] = $component;
      }
    }
    foreach ($values['regions'] as $key => $region) {
      uasort($region, [SortArray::class, 'sortByWeightElement']);
      $values['regions'][$key] = array_keys($region);
    }

    // Set the settings for the entity display form.
    foreach (['design', 'settings', 'content', 'regions'] as $key) {
      if (!empty($values[$key])) {
        $entity->setThirdPartySetting('designs_entity', $key, $values[$key]);
      }
      else {
        $entity->unsetThirdPartySetting('designs_entity', $key);
      }
    }
  }

  /**
   * Rebuild the form using the selected pattern.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function multistepSubmit(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $op = $trigger['#op'];
    switch ($op) {
      case 'remove_content':
        $entity_display_form = $form_state->getBuildInfo()['callback_object'];
        /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $entity */
        $entity = $entity_display_form->getEntity();
        $entity->removeComponent($trigger['#index']);
        $form_state->setRebuild();
        break;
    }
  }

  /**
   * Since the whole form changes with the regions and design settings.
   *
   * @param array $form
   *   The built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The built form.
   */
  public static function multistepAjax(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Get the regions as used by the field_ui_table.
   *
   * @param \Drupal\designs\DesignInterface $design
   *   The design.
   */
  protected function getRegions(DesignInterface $design) {
    $regions = [];
    foreach ($design->getPluginDefinition()->getRegions() as $key => $content) {
      $regions[$key] = [
        'title' => $content['label'],
        'message' => t('No field is displayed.'),
      ];
    }
    $regions['hidden'] = [
      'title' => t('Hidden'),
      'message' => t('No field is hidden.'),
    ];
    return $regions;
  }

  /**
   * Get the region labels.
   *
   * @param \Drupal\designs\DesignInterface $design
   *   The design.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   The region labels.
   */
  protected function getRegionLabels(DesignInterface $design) {
    $regions = $this->getRegions($design);
    return array_map(function ($item) {
      return $item['title'];
    }, $regions);
  }

  /**
   * Get the row region for the field.
   *
   * @param array $row
   *   The row element.
   *
   * @return string
   *   The region value.
   */
  public function getRowRegion(array &$row) {
    $regions = $row['region']['#options'];
    if (!isset($regions[$row['region']['#value']])) {
      $row['region']['#value'] = 'hidden';
    }
    return $row['region']['#value'];
  }

  /**
   * Builds the table row structure for a single extra field.
   *
   * @param string $field_id
   *   The field ID.
   * @param array $display_options
   *   The display options.
   * @param array $extra_field
   *   The pseudo-field element.
   * @param array $regions
   *   The region labels.
   *
   * @return array
   *   A table row array.
   */
  protected function buildExtraFieldRow($field_id, array $display_options, array $extra_field, array $regions) {
    return [
      '#attributes' => ['class' => ['draggable', 'tabledrag-leaf']],
      '#row_type' => 'extra_field',
      '#region_callback' => [$this, 'getRowRegion'],
      '#js_settings' => ['rowHandler' => 'field'],
      'human_name' => [
        '#markup' => $extra_field['label'],
      ],
      'weight' => [
        '#type' => 'textfield',
        '#title' => $this->t('Weight for @title', ['@title' => $extra_field['label']]),
        '#title_display' => 'invisible',
        '#default_value' => $display_options ? $display_options['weight'] : 0,
        '#size' => 3,
        '#attributes' => ['class' => ['field-weight']],
      ],
      'parent_wrapper' => [
        'parent' => [
          '#type' => 'select',
          '#title' => $this->t('Parents for @title', ['@title' => $extra_field['label']]),
          '#title_display' => 'invisible',
          '#options' => array_combine(array_keys($regions), array_keys($regions)),
          '#empty_value' => '',
          '#attributes' => ['class' => ['js-field-parent', 'field-parent']],
          '#parents' => ['fields', $field_id, 'parent'],
        ],
        'hidden_name' => [
          '#type' => 'hidden',
          '#default_value' => $field_id,
          '#attributes' => ['class' => ['field-name']],
        ],
      ],
      'region' => [
        '#type' => 'select',
        '#title' => $this->t('Region for @title', ['@title' => $extra_field['label']]),
        '#title_display' => 'invisible',
        '#options' => $regions,
        '#default_value' => $display_options ? $display_options['region'] : 'hidden',
        '#attributes' => ['class' => ['field-region']],
      ],
      'plugin' => [
        'type' => [
          '#type' => 'hidden',
          '#value' => $display_options ? 'visible' : 'hidden',
          '#parents' => ['fields', $field_id, 'type'],
          '#attributes' => ['class' => ['field-plugin-type']],
        ],
      ],
      'settings_summary' => [],
      'settings_edit' => [],
    ];
  }

}
