<?php

namespace Drupal\designs\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the configuration form for a design.
 */
class ConfigurationForm extends FormBase {

  /**
   * Build the configuration form for a plugin.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    if (!$this->design) {
      return $form;
    }
    $design_parents = $form['#array_parents'] ?? $form['#parents'];
    $form['#design_parents'] = $design_parents;

    // Update the form to be a wrapper.
    if (!isset($form['#contents_wrapper'])) {
      $form['#contents_wrapper'] = [
        'wrapper' => self::getElementId($form['#parents']),
      ];
      $form['#prefix'] = '<div id="' . $form['#contents_wrapper']['wrapper'] . '">';
      $form['#suffix'] = '</div>';
    }

    // Custom content reloads the entire configuration for the plugin, unless
    // Unless the ajax targets higher.
    $source_plugin = $this->getDesign()->getSourcePlugin();
    $form['#design_contexts'] = $source_plugin->getFormContexts();

    // Create the settings form.
    $form['settings'] = $this->buildSubform('settings', SettingsForm::class, $form, $form_state);

    // Create the custom content form.
    if ($source_plugin->usesCustomContent()) {
      $form['content'] = $this->buildSubform('content', ContentsForm::class, $form, $form_state);
    }

    // Create the content and custom content forms.
    if ($source_plugin->usesRegionsForm()) {
      $form['regions'] = $this->buildSubform('regions', RegionsForm::class, $form, $form_state);
    }

    return $form;
  }

  /**
   * Validation of ::buildForm().
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    foreach (['settings', 'content', 'regions'] as $target) {
      if (!empty($form[$target]['#form_handler'])) {
        $form[$target]['#form_handler']->validateForm($form[$target], $form_state);
      }
    }
  }

  /**
   * Submission of ::buildForm().
   */
  public function submitForm(array $form, FormStateInterface $form_state) {
    $design = $this->getDesign();
    $result = [
      'design' => $design ? $design->getPluginId() : '',
    ];
    if ($design) {
      foreach (['settings', 'content', 'regions'] as $target) {
        if (!empty($form[$target]['#form_handler'])) {
          $result[$target] = $form[$target]['#form_handler']->submitForm($form[$target], $form_state);
        }
      }
    }
    $form_state->setValue($form['#parents'], $result);
    return $result;
  }

  /**
   * Build a subform form.
   *
   * @param string $target
   *   The target subform.
   * @param string $class
   *   The form class.
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form.
   */
  protected function buildSubform($target, $class, array $form, FormStateInterface $form_state) {
    $element = self::getChildElement(
      array_merge($form['#parents'], [$target]),
      $form
    );

    $form_handler = new $class(
      $this->manager,
      $this->settingManager,
      $this->contentManager,
    );

    return $form_handler
      ->setDesign($this->getDesign())
      ->buildForm($element, $form_state);
  }

}
