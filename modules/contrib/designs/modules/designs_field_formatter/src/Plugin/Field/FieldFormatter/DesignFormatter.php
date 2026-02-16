<?php

namespace Drupal\designs_field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\designs\DesignContentManagerInterface;
use Drupal\designs\DesignManagerInterface;
use Drupal\designs\DesignSettingManagerInterface;
use Drupal\designs\Form\PluginForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'design' formatter.
 *
 * @FieldFormatter(
 *   id = "design_formatter",
 *   label = @Translation("Design"),
 *   field_types = {
 *   },
 * )
 */
class DesignFormatter extends FormatterBase {

  /**
   * The design manager.
   *
   * @var \Drupal\designs\DesignManagerInterface
   */
  protected $designManager;

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->designManager = $container->get('plugin.manager.design');
    $instance->settingManager = $container->get('plugin.manager.design_setting');
    $instance->contentManager = $container->get('plugin.manager.design_content');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'design' => '',
      'settings' => [],
      'content' => [],
      'regions' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      '#tree' => TRUE,
      '#process' => [[$this, 'settingsProcess']],
      '#element_validate' => [[$this, 'settingsValidate']],
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * Render API: Process callback for the settingsForm.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $complete_form
   *   The complete form.
   *
   * @return array
   *   The updated form element.
   */
  public function settingsProcess(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $field_definition = $this->fieldDefinition;
    $settings = $this->getSettings();
    $plugin_form = new PluginForm(
      $this->designManager,
      $this->settingManager,
      $this->contentManager,
      $settings['design'],
      $settings,
      'field_formatter',
      [
        'type' => $field_definition->getTargetEntityTypeId(),
        'bundle' => $field_definition->getTargetBundle(),
        'field' => $field_definition->getName(),
      ]
    );
    return $plugin_form->buildForm($element, $form_state);
  }

  /**
   * Validation of ::settingsForm().
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state.
   */
  public function settingsValidate(array $form, FormStateInterface $form_state) {
    $form['#form_handler']->validateForm($form, $form_state);
  }

  /**
   * Submission of ::settingsForm().
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state.
   */
  public function settingsSubmit(array $form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    $design_parents = $trigger_element['#design_parents'] ?? [];
    $element = NestedArray::getValue($form, $design_parents);
    if (isset($element['#form_handler'])) {
      $element['#form_handler']->submitForm($element, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    // Get the field design.
    $configuration = $this->getSettings();
    $design = $this->designManager->createSourcedInstance(
      $configuration['design'],
      $configuration,
      'field_formatter',
      [
        'type' => $this->fieldDefinition->getTargetEntityTypeId(),
        'bundle' => $this->fieldDefinition->getTargetBundle(),
        'field' => $this->fieldDefinition->getName(),
      ],
    );

    $summary = [];
    if ($design) {
      $summary[] = $this->t('Design: @design', [
        '@design' => $design->getPluginDefinition()->getLabel(),
      ]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Get the field design via configuration.
    $configuration = $this->getSettings();
    $design = $this->designManager->createSourcedInstance(
      $configuration['design'],
      $configuration,
      'field_formatter',
      [
        'type' => $this->fieldDefinition->getTargetEntityTypeId(),
        'bundle' => $this->fieldDefinition->getTargetBundle(),
        'field' => $this->fieldDefinition->getName(),
      ],
    );

    // Process the individual fields with designs.
    $elements = [];
    if ($design) {
      $entity = $items->getEntity();
      foreach ($items as $delta => $item) {
        $element[$delta] = [
          '#entity' => $entity,
          '#entity_type' => $entity->getEntityTypeId(),
          '#bundle' => $entity->bundle(),
          '#field_name' => $this->fieldDefinition->getName(),
          '#delta' => $delta,
          '#item' => $item,
        ];
        $elements[$delta] = $design->build($element[$delta]);
      }
    }

    return $elements;
  }

}
