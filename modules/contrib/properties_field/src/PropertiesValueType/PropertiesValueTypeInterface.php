<?php

namespace Drupal\properties_field\PropertiesValueType;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an interface for the properties value type plugins.
 */
interface PropertiesValueTypeInterface extends PluginInspectionInterface, ConfigurableInterface {

  /**
   * Build the widget settings form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The widget settings form.
   */
  public function widgetSettingsForm(array $form, FormStateInterface $form_state, array &$complete_form);

  /**
   * Build the widget settings summary.
   *
   * @return string[]|\Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   The widget settings summary.
   */
  public function widgetSettingsSummary();

  /**
   * Get the widget form.
   *
   * @param array $element
   *   The value form element.
   * @param mixed $value
   *   The raw value or NULL if empty.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The value form.
   */
  public function widgetForm(array $element, $value, FormStateInterface $form_state);

  /**
   * Build the formatter settings form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The formatter settings form.
   */
  public function formatterSettingsForm(array $form, FormStateInterface $form_state, array &$complete_form);

  /**
   * Build the formatter settings summary.
   *
   * @return string[]|\Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   The formatter settings summary or NULL if not applicable.
   */
  public function formatterSettingsSummary();

  /**
   * Render the value.
   *
   * @param mixed $value
   *   The raw value.
   *
   * @return array|string|\Drupal\Component\Render\MarkupInterface
   *   The rendered value.
   */
  public function formatterRender($value);

}
