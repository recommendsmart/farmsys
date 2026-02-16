<?php

namespace Drupal\designs_view\Form\Ajax;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\designs\Form\PluginForm;
use Drupal\views\ViewEntityInterface;
use Drupal\views_ui\Form\Ajax\ViewsFormBase;
use Drupal\views_ui\ViewUI;

/**
 * Provides a form for managing a design for the views UI.
 */
class DesignHandler extends ViewsFormBase {

  /**
   * Constructs a new DesignHandler object.
   */
  public function __construct($type = NULL) {
    $this->setType($type);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormKey() {
    return 'design';
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(ViewEntityInterface $view, $display_id, $js, $type = NULL) {
    $this->setType($type);
    return parent::getForm($view, $display_id, $js);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_ui_design_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $view = $form_state->get('view');
    $display_id = $form_state->get('display_id');
    $type = $form_state->get('type');

    $executable = $view->getExecutable();
    if (!$executable->setDisplay($display_id)) {
      $form['markup'] = ['#markup' => $this->t('Invalid display id @display', ['@display' => $display_id])];
      return $form;
    }
    /** @var \Drupal\views\Plugin\views\display\DisplayPluginBase $display */
    $display = &$executable->displayHandlers->get($display_id);

    $areas = [
      'header' => $this->t('Header'),
      'footer' => $this->t('Footer'),
      'empty' => $this->t('No results behaviour'),
      'pager' => $this->t('Pager'),
    ];

    $form['#title'] = $this->t('@type design', ['@type' => $areas[$type]]);
    $form['#section'] = $display_id . 'design-handler';

    // Design the display override dropdown.
    views_ui_standard_display_dropdown($form, $form_state, $type);

    $options = $display->getOption('design')[$type];

    $design_form = new PluginForm(
      \Drupal::service('plugin.manager.design'),
      \Drupal::service('plugin.manager.design_setting'),
      \Drupal::service('plugin.manager.design_content'),
      $options['design'],
      $options,
      $this->getSource($type),
      $this->getSources($type, $display->getOption($type)),
    );

    $form['#prefix'] = '<div class="scroll" data-drupal-views-scroll>';
    $form['#suffix'] = '</div>';

    $form['design'] = [
      '#parents' => ['design'],
      '#design_submit' => [
        [$form_state->getFormObject(), 'submitForm'],
        [static::class, 'multistepSubmit'],
      ],
      '#design_ajax' => [
        'url' => Url::fromRoute('views_ui.form_design', [
          'js' => $form_state->get('ajax') ? 'ajax' : 'nojs',
          'view' => $form_state->get('view')->id(),
          'display_id' => $form_state->get('display_id'),
          'type' => $form_state->get('type'),
        ]),
      ],

    ];
    $form['design'] = $design_form->buildForm($form['design'], $form_state);
    $form['design']['#type'] = 'container';
    $form['design']['#attached']['library'][] = 'designs_view/views-admin';

    $view->getStandardButtons($form, $form_state, 'views_ui_design_form');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $form['design']['#form_handler']->validateForm($form['design'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form['design']['#form_handler']->submitForm($form['design'], $form_state);

    $view = $form_state->get('view');
    $display_id = $form_state->get('display_id');
    $type = $form_state->get('type');
    $display = &$view->getExecutable()->displayHandlers->get($display_id);

    // Update the options using the design extender.
    $options = $display->getOption('design');

    $design = $form_state->getValue('design');
    if (!empty($design['design'])) {
      $options[$type] = $design;
    }
    else {
      unset($options[$type]);
    }

    $display->setOption('design', $options);

    // Store in cache.
    $view->cacheSet();
  }

  /**
   * Get the source from the type.
   *
   * @param string $type
   *   The views handler type.
   *
   * @return string
   *   The source plugin identifier.
   */
  protected function getSource($type) {
    return $type === 'pager' ? 'views_pager' : 'views_area';
  }

  /**
   * Get the source configuration passed through to the source plugin.
   *
   * @param string $type
   *   The type.
   * @param array $option
   *   The options.
   *
   * @return array
   *   The source configuration.
   */
  protected function getSources($type, array $option) {
    if ($type !== 'pager') {
      $results = [];
      foreach ($option as $key => $value) {
        $results[$key] = $value['admin_label'] ?: $key;
      }
      return $results;
    }
    return $option;
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
      $view->addFormToStack('design', $display_id, $type);
    }
  }

}
