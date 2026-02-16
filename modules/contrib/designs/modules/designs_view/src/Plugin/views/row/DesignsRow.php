<?php

namespace Drupal\designs_view\Plugin\views\row;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\designs\DesignContentManagerInterface;
use Drupal\designs\DesignManagerInterface;
use Drupal\designs\DesignSettingManagerInterface;
use Drupal\designs\Form\PluginForm;
use Drupal\views\Plugin\views\row\RowPluginBase;
use Drupal\views_ui\ViewUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The 'design' row plugin.
 *
 * This displays fields using a design.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "design",
 *   title = @Translation("Design"),
 *   help = @Translation("Displays the fields using a design."),
 * )
 */
class DesignsRow extends RowPluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

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
   * Provide a form for setting options.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form += [
      '#parents' => ['row_options'],
      '#array_parents' => ['options', 'row_options'],
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

    /** @var \Drupal\views\ViewExecutable $view */
    $view = $form_state->get('view')->getExecutable();

    $design_form = new PluginForm(
      $this->designManager,
      $this->settingManager,
      $this->contentManager,
      $design['design'],
      $design,
      'views_row',
      $view->display_handler->getFieldLabels(),
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
    $form['design']['#form_handler']->submitForm($form['design'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    $result = parent::render($row);

    $options = $this->options['design'];
    if (empty($options['design'])) {
      return $result;
    }

    $design = $this->designManager->createSourcedInstance(
      $options['design'],
      $options,
      'views_row',
      $this->view->display_handler->getFieldLabels(),
    );
    if (!$design) {
      return $result;
    }

    // Since most of the rendering is in template_preprocess_views_view_field()
    // We emulate the general behaviour of the fields here as render elements.
    $element = [
      '#view' => $this->view,
      '#row' => $row,
    ];
    foreach ($this->view->field as $id => $field) {
      $field_output = $this->view->style_plugin->getField($row->index, $id);
      $empty = $field->isValueEmpty($field_output, $field->options['empty_zero']);
      if (empty($field->options['exclude']) && (!$empty || (empty($field->options['hide_empty']) && empty($variables['options']['hide_empty'])))) {
        $element[$id] = [
          '#markup' => $field_output,
        ];
      }
    }

    return $design->build($element);
  }

  /**
   * {@inheritdoc}
   */
  public function themeFunctions() {
    return $this->view->buildThemeFunctions('views_view_fields');
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

}
