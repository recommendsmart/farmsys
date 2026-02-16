<?php

namespace Drupal\designs\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\designs\DesignContentManagerInterface;
use Drupal\designs\DesignManagerInterface;
use Drupal\designs\DesignSettingManagerInterface;

/**
 * Provides the plugin form behaviour for design configuration.
 */
class PluginForm extends ConfigurationForm {

  /**
   * The design manager.
   *
   * @var \Drupal\designs\DesignManagerInterface
   */
  protected $manager;

  /**
   * The design plugin identifier.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The design plugin configuration.
   *
   * @var array
   */
  protected $pluginConfiguration;

  /**
   * The design source identifier.
   *
   * @var string
   */
  protected $sourceId;

  /**
   * The design source configuration.
   *
   * @var array
   */
  protected $sourceConfiguration;

  /**
   * PluginForm constructor.
   *
   * @param \Drupal\designs\DesignManagerInterface $manager
   *   The design manager.
   * @param \Drupal\designs\DesignSettingManagerInterface $settingManager
   *   The design setting manager.
   * @param \Drupal\designs\DesignContentManagerInterface $contentManager
   *   The design content manager.
   * @param string $plugin_id
   *   The plugin identifier.
   * @param array $plugin_configuration
   *   The plugin configuration.
   * @param string $source_id
   *   The source identifier.
   * @param array $source_configuration
   *   The source configuration.
   */
  public function __construct(
    DesignManagerInterface $manager,
    DesignSettingManagerInterface $settingManager,
    DesignContentManagerInterface $contentManager,
    $plugin_id,
    array $plugin_configuration,
    $source_id,
    array $source_configuration
  ) {
    parent::__construct($manager, $settingManager, $contentManager);
    $this->pluginId = $plugin_id;
    $this->pluginConfiguration = $plugin_configuration;
    $this->sourceId = $source_id;
    $this->sourceConfiguration = $source_configuration;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTitle() {
    return $this->t('Design');
  }

  /**
   * {@inheritdoc}
   */
  protected function getPlugin(string $plugin, array $values) {
    return $this->manager->createSourcedInstance(
      $plugin,
      $values,
      $this->sourceId,
      $this->sourceConfiguration
    );
  }

  /**
   * Build the configuration form for the design.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $design_parents = $form['#array_parents'] ?? $form['#parents'];
    $configuration = $form_state->getValue($form['#parents']);
    if (!$configuration) {
      $configuration = $this->pluginConfiguration;
      $form_state->setValue($form['#parents'], $configuration);
    }

    // Ensure the basics for each of the configurations.
    $configuration += [
      'design' => '',
      'settings' => [],
      'content' => [],
      'regions' => [],
    ];

    // Set the design used for the forms.
    $this->setDesign($this->getPlugin($configuration['design'], $configuration));

    // There is no form when there are no options.
    $options = [
      '' => $this->t('- Use default behaviour -'),
    ] + $this->manager->getDesignOptions();
    if (count($options) < 2) {
      return parent::buildForm($form, $form_state);
    }

    // The plugin configuration is empty so use default behaviour.
    $design = $this->getDesign();
    if ($design && empty($configuration['regions'])) {
      $design->setDefaultDesign();
    }

    $submit_name = self::getElementId($form['#parents'], '-submit');
    $form += [
      '#design_parents' => $design_parents,
      '#prefix' => '<div id="' . self::getElementId($form['#parents']) . '">',
      '#suffix' => '</div>',
      '#type' => 'details',
      '#title' => $this->t('Design'),
      '#tree' => TRUE,
      '#design' => $this->design,
      '#open' => self::isFocussed($form['#parents'], $form_state),
      'design' => [
        '#type' => 'select',
        '#op' => 'change_design',
        '#title' => t('Design'),
        '#options' => $options,
        '#default_value' => $this->design ? $this->design->getPluginId() : '',
        '#ajax' => self::getAjax($form),
        '#design_parents' => $design_parents,
      ],
      'submit' => [
        '#type' => 'submit',
        '#op' => 'change_design',
        '#name' => $submit_name,
        '#value' => $this->t('Change design'),
        '#submit' => self::getSubmit($form),
        '#attributes' => ['class' => ['js-hide']],
        '#ajax' => self::getAjax($form),
        '#design_parents' => $design_parents,
      ],
    ];

    return parent::buildForm($form, $form_state);
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
    $values = $form_state->getValue($form['#parents']);
    $design = $values['design'] ?? '';
    if ($form['design']['#default_value'] === $design) {
      parent::validateForm($form, $form_state);
    }
  }

  /**
   * Submission of ::buildForm().
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    unset($values['submit']);

    $design = $this->manager->createSourcedInstance(
      $values['design'] ?? '',
      $values ?: [],
      $this->sourceId,
      $this->sourceConfiguration
    );
    $form['#design'] = $design;
    $this->setDesign($design);

    // Process the form values.
    if ($design) {
      $values = $design->getConfiguration();
    }
    else {
      $values = [];
    }
    $form_state->setValue($form['#parents'], $values);

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function massageFormValues(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    unset($values['submit']);
    $form_state->setValue($form['#parents'], $values);
  }

  /**
   * {@inheritdoc}
   */
  public static function multistepAjax(array $form, FormStateInterface $form_state) {
    $triggered_element = $form_state->getTriggeringElement();
    switch ($triggered_element['#op']) {
      case 'change_design':
        $parents = $triggered_element['#design_parents'];
        return NestedArray::getValue($form, $parents);

      default:
        return parent::multistepAjax($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function multistepSubmit(array $form, FormStateInterface $form_state) {
    $triggered_element = $form_state->getTriggeringElement();
    switch ($triggered_element['#op']) {
      case 'change_design':
        // Get the values.
        $parents = array_slice($triggered_element['#parents'], 0, -1);
        $values = $form_state->getValue($parents);

        // Get the form element.
        $array_parents = array_slice($triggered_element['#array_parents'], 0, -1);
        $element = NestedArray::getValue($form, $array_parents);

        // Clear everything else except the design choice when selecting
        // a different design.
        $design = $values['design'] ?? '';
        if ($element['design']['#default_value'] !== $design) {
          $values = [
            'design' => $design,
          ];
          $form_state->setValue($parents, $values);
        }

        // Always rebuild.
        $form_state->setRebuild();
        break;
    }
  }

}
