<?php

namespace Drupal\designs_layout\Plugin\Layout;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Layout\LayoutDefinition;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\designs\DesignContentManagerInterface;
use Drupal\designs\DesignManagerInterface;
use Drupal\designs\DesignSettingManagerInterface;
use Drupal\designs\Form\SettingsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a layout plugin that uses a design.
 *
 * @Layout(
 *   id = "design",
 *   deriver = "\Drupal\designs_layout\Plugin\Derivative\DesignLayoutDeriver"
 * )
 */
class DesignLayout extends LayoutDefault implements PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * The element info.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * The design manager.
   *
   * @var \Drupal\designs\DesignManagerInterface
   */
  protected $designManager;

  /**
   * Module Handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
   * Constructs a DesignLayout object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param \Drupal\Core\Layout\LayoutDefinition $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   Element info object.
   * @param \Drupal\designs\DesignManagerInterface $designManager
   *   The design manager.
   * @param \Drupal\designs\DesignSettingManagerInterface $settingManager
   *   The design settings manager.
   * @param \Drupal\designs\DesignContentManagerInterface $contentManager
   *   The design content manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    LayoutDefinition $plugin_definition,
    ModuleHandlerInterface $module_handler,
    ElementInfoManagerInterface $element_info,
    DesignManagerInterface $designManager,
    DesignSettingManagerInterface $settingManager,
    DesignContentManagerInterface $contentManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->elementInfo = $element_info;
    $this->moduleHandler = $module_handler;
    $this->designManager = $designManager;
    $this->settingManager = $settingManager;
    $this->contentManager = $contentManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('plugin.manager.element_info'),
      $container->get('plugin.manager.design'),
      $container->get('plugin.manager.design_setting'),
      $container->get('plugin.manager.design_content')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label' => '',
      'settings' => [],
    ];
  }

  /**
   * Get the design used for the layout.
   *
   * @return \Drupal\designs\DesignInterface|null
   *   The design identifier.
   */
  protected function getDesign() {
    $definition = $this->getPluginDefinition();
    $design_id = $definition->get('design');

    // Always map the regions to regions.
    $regions = [];
    foreach ($this->designManager->getDefinition($design_id)->getRegionNames() as $region) {
      $regions[$region] = [$region];
    }

    return $this->designManager->createSourcedInstance(
      $design_id,
      ['settings' => $this->configuration['settings'], 'regions' => $regions],
      'layout',
      ['design' => $design_id]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['#parents'] = ['layout_options'];
    $form = parent::buildConfigurationForm($form, $form_state);

    // Get the design and main form state.
    $design = $this->getDesign();
    $form_state = $form_state instanceof SubformState ?
      $form_state->getCompleteFormState() :
      $form_state;

    // Always ensure the design configuration has been set for the form value.
    $parents = array_merge($form['#parents'], ['settings']);
    $values = $form_state->getValue($parents);
    if (!$values) {
      $form_state->setValue($parents, $design->getConfiguration()['settings']);
    }
    else {
      $configuration = $design->getConfiguration();
      $configuration['settings'] = $values;
      $design->setConfiguration($configuration);
    }

    // Ensure that the configuration form indicates the design.
    $label = $design->getPluginDefinition()->getLabel();
    $form['design'] = [
      '#markup' => $this->t('<h3>Design: %design</h3>', ['%design' => $label]),
    ];

    $settings_form = (new SettingsForm(
      $this->designManager,
      $this->settingManager,
      $this->contentManager,
    ))->setDesign($design);

    $form['settings'] = [
      '#parents' => $parents,
      '#design_parents' => $parents,
    ];
    $form['settings'] = $settings_form->buildForm($form['settings'], $form_state);
    $form['settings']['#open'] = TRUE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $form_state = $form_state instanceof SubformState ?
      $form_state->getCompleteFormState() :
      $form_state;
    $form['settings']['#form_handler']->validateForm($form['settings'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $form_state = $form_state instanceof SubformState ?
      $form_state->getCompleteFormState() :
      $form_state;
    $values = $form['settings']['#form_handler']->submitForm($form['settings'], $form_state);
    $this->configuration['settings'] = $values;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    return $this->getDesign()->build($regions) + [
      '#layout' => $this->getPluginDefinition(),
    ];
  }

}
