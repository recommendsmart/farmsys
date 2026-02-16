<?php

namespace Drupal\designs_view\Plugin\views\style;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\designs\DesignContentManagerInterface;
use Drupal\designs\DesignManagerInterface;
use Drupal\designs\DesignSettingManagerInterface;
use Drupal\designs\Form\PluginForm;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\views_ui\ViewUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Views style that renders a design.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "designs",
 *   title = @Translation("Design"),
 *   help = @Translation("Uses a design to arrange views elements."),
 * )
 */
class DesignsStyle extends StylePluginBase implements ContainerFactoryPluginInterface {

  /**
   * Use the row plugin.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * No grouping supported.
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;

  /**
   * Does not use the row classes.
   *
   * @var bool
   */
  protected $usesRowClass = FALSE;

  /**
   * Uses options.
   *
   * @var bool
   */
  protected $usesOptions = TRUE;

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
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['design'] = [
      'default' => [
        'design' => '',
        'settings' => [],
        'content' => [],
        'regions' => [],
      ],
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form += [
      '#parents' => ['style_options'],
      '#array_parents' => ['options', 'style_options'],
    ];

    // Views has a weird way of processing forms, meaning the form_state
    // is not properly populated on multistep content.
    $parents = array_merge($form['#parents'], ['design']);
    $input = $form_state->getUserInput();
    $input = NestedArray::getValue($input, $parents);
    $design = $input ?: $this->options['design'];
    if ($input) {
      $form_state->setValue($parents, $input);
    }

    $design_form = new PluginForm(
      $this->designManager,
      $this->settingManager,
      $this->contentManager,
      $design['design'],
      $design,
      'views_style',
      [],
    );

    $form['design'] = [
      '#parents' => array_merge($form['#parents'], ['design']),
      '#array_parents' => array_merge($form['#array_parents'], ['design']),
      '#design_submit' => [
        [$form_state->getFormObject(), 'submitForm'],
        [static::class, 'multistepSubmit'],
      ],
      '#design_ajax' => [
        'url' => Url::fromRoute('views_ui.form_display', [
          'js' => $form_state->get('ajax') ? 'ajax' : 'nojs',
          'view' => $form_state->get('view')->id(),
          'display_id' => $form_state->get('display_id'),
          'type' => $form_state->get('type'),
        ]),
      ],
    ];
    $form['design'] = $design_form->buildForm($form['design'], $form_state);
    $form['design']['#type'] = 'container';
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);
    $form['design']['#form_handler']->validateForm($form['design'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    $this->options['design'] = $form['design']['#form_handler']->submitForm($form['design'], $form_state);
  }

  /**
   * Handle Views UI multistep behaviour.
   *
   * Views UI has a multistep behaviour that is not the same as the default
   * form API, so we have to mimic the expected behaviour.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function multistepSubmit(array $form, FormStateInterface $form_state) {
    $type = $form_state->get('type');
    $display_id = $form_state->get('display_id');
    $view = $form_state->get('view');
    if ($view instanceof ViewUI) {
      $view->addFormToStack('display', $display_id, $type);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $rows = [];
    $row_plugin = $this->usesRowPlugin();
    foreach ($this->view->result as $index => $row) {
      $this->view->row_index = $index;
      $rows[$index] = $row_plugin ? $this->view->rowPlugin->render($row) : $row;
    }
    unset($this->view->row_index);

    return [
        '#design' => $this->options['design'],
      ] + $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function themeFunctions() {
    return $this->view->buildThemeFunctions('views_view_design');
  }

  /**
   * {@inheritdoc}
   */
  public function evenEmpty() {
    return TRUE;
  }

}
