<?php

namespace Drupal\designs\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\designs\DesignContentManagerInterface;
use Drupal\designs\DesignInterface;
use Drupal\designs\DesignManagerInterface;
use Drupal\designs\DesignSettingManagerInterface;

/**
 * Provides common behaviour for design forms.
 */
abstract class FormBase {

  use DependencySerializationTrait;
  use StringTranslationTrait;
  use FormTrait;

  /**
   * The design being configured.
   *
   * @var \Drupal\designs\DesignInterface|null
   */
  protected $design = NULL;

  /**
   * The design manager.
   *
   * @var \Drupal\designs\DesignManagerInterface
   */
  protected $manager;

  /**
   * The design settings manager.
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
   * FormBase constructor.
   *
   * @param \Drupal\designs\DesignManagerInterface $manager
   *   The design manager.
   * @param \Drupal\designs\DesignSettingManagerInterface $settingManager
   *   The design setting manager.
   * @param \Drupal\designs\DesignContentManagerInterface $contentManager
   *   The design content manager.
   */
  public function __construct(
    DesignManagerInterface $manager,
    DesignSettingManagerInterface $settingManager,
    DesignContentManagerInterface $contentManager
  ) {
    $this->manager = $manager;
    $this->settingManager = $settingManager;
    $this->contentManager = $contentManager;
  }

  /**
   * Get the design for the form.
   *
   * @return \Drupal\designs\DesignInterface|null
   *   The design.
   */
  public function getDesign() {
    return $this->design;
  }

  /**
   * Set the design for the form.
   *
   * @param \Drupal\designs\DesignInterface|null $design
   *   The design.
   *
   * @return $this
   *   The object instance.
   */
  public function setDesign(?DesignInterface $design) {
    $this->design = $design;
    return $this;
  }

  /**
   * Get the title used for the form.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The title.
   */
  protected function getTitle() {
    return '';
  }

  /**
   * Build the configuration form for the design.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form += [
      '#form_handler' => $this,
      '#type' => 'details',
      '#title' => $this->getTitle(),
      '#open' => self::isFocussed($form['#parents'], $form_state),
      '#element_validate' => [[static::class, 'massageFormValues']],
    ];
    $design = $this->getDesign();
    if ($design) {
      $form['#design'] = $design;
    }
    return $form;
  }

  /**
   * Validation of ::buildForm().
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
  }

  /**
   * Submission of ::buildForm().
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The values from the submission.
   */
  public function submitForm(array $form, FormStateInterface $form_state) {
    return $form_state->getValue($form['#parents']);
  }

  /**
   * Massage the form values into final values.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function massageFormValues(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    unset($values['submit']);
    unset($values['remove']);
    $form_state->setValue($form['#parents'], $values);
  }

  /**
   * Get ajax handlers from the form.
   *
   * @param array $form
   *   The form array.
   *
   * @return array
   *   The ajax value.
   */
  protected static function getAjax(array $form) {
    return ($form['#design_ajax'] ?? []) + [
      'callback' => [static::class, 'multistepAjax'],
      'wrapper' => static::getElementId($form['#parents']),
      'effect' => 'fade',
    ];
  }

  /**
   * Get the submit handlers for a form element.
   *
   * @param array $form
   *   The existing form.
   *
   * @return array[]
   *   The submit handler.
   */
  protected static function getSubmit(array $form) {
    return array_merge(
      [[static::class, 'multistepSubmit']],
      $form['#design_submit'] ?? [],
    );
  }

  /**
   * Check form item is currently being manipulated.
   *
   * @param array $parents
   *   The form parents array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   The result.
   */
  protected static function isFocussed(array $parents, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    if ($trigger_element) {
      $trigger_parents = array_slice($trigger_element['#parents'], 0, count($parents));
      return ($trigger_parents == $parents);
    }
    return FALSE;
  }

  /**
   * Generates a child form element with optional ajax/submit support.
   *
   * @param array $parents
   *   The child parents.
   * @param array $form
   *   The parent form array.
   *
   * @return array[]
   *   The child form array.
   */
  protected static function getChildElement(array $parents, array $form) {
    $targets = [
      '#design_ajax',
      '#design_submit',
      '#contents_wrapper',
      '#design_parents',
      '#design_contexts',
    ];
    $child = ['#parents' => $parents];
    foreach ($targets as $target) {
      if (isset($form[$target])) {
        $child[$target] = $form[$target];
      }
    }
    return $child;
  }

  /**
   * Generate the plugin configuration form.
   *
   * @param array $plugins
   *   The plugin selection.
   * @param object|null $plugin
   *   The plugin instance.
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form array.
   */
  protected function buildPluginForm(array $plugins, $plugin, array $form, FormStateInterface $form_state) {
    $parents = $form['#parents'];

    // Use form specified extension to #ajax and #submit.
    $ajax = static::getAjax($form);

    $form += [
      '#prefix' => '<div id="' . static::getElementId($form['#parents']) . '">',
      '#suffix' => '</div>',
      '#parents' => $parents,
      '#type' => 'details',
      '#open' => static::isFocussed($parents, $form_state),
      'plugin' => [
        '#type' => 'select',
        '#op' => 'change_plugin',
        '#options' => $plugins,
        '#default_value' => $plugin ? $plugin->getPluginId() : key($plugins),
        '#ajax' => $ajax,
        '#parents' => array_merge($parents, ['plugin']),
        '#design_parents' => $form['#design_parents'],
      ],
      'submit' => [
        '#type' => 'submit',
        '#op' => 'change_plugin',
        '#value' => $this->t('Change plugin'),
        '#submit' => static::getSubmit($form),
        '#name' => static::getElementId($parents, '-submit'),
        '#attributes' => ['class' => ['js-hide']],
        '#ajax' => $ajax,
        '#parents' => array_merge($parents, ['submit']),
        '#design_parents' => $form['#design_parents'],
      ],
      'config' => self::getChildElement(array_merge($parents, ['config']), $form) + [
        '#prefix' => '<div id="' . static::getElementId($form['#parents'], '-config') . '">',
        '#suffix' => '</div>',
        '#wrapper_id' => static::getElementId($form['#parents'], '-config'),
        '#design' => $this->getDesign(),
        '#tree' => TRUE,
        '#design_parents' => $form['#design_parents'],
      ],
    ];

    // Generate the plugin configuration form.
    if ($plugin) {
      $form['config'] = $plugin->buildConfigurationForm($form['config'], $form_state);
    }

    return $form;
  }

  /**
   * Ajax handler for the buildForm().
   */
  public static function multistepAjax(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    switch ($trigger['#op']) {
      case 'change_plugin':
        $parents = array_slice($trigger['#array_parents'], 0, -1);
        return NestedArray::getValue($form, $parents);

      default:
        return $form;
    }
  }

  /**
   * Submit handler for the buildForm().
   */
  public static function multistepSubmit(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    switch ($trigger['#op']) {
      case 'change_plugin':
        // Clear everything else except the plugin choice.
        $parents = array_slice($trigger['#parents'], 0, -1);
        $values = $form_state->getValue($parents);

        // Get the form element.
        $array_parents = array_slice($trigger['#array_parents'], 0, -1);
        $element = NestedArray::getValue($form, $array_parents);
        if ($element['plugin']['#value'] !== $values['plugin']) {
          $values = [
            'plugin' => $values['plugin'],
          ];
          $form_state->setValue($parents, $values);
        }

        $form_state->setRebuild();
        break;
    }
  }

}
