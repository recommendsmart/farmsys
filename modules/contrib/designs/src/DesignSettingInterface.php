<?php

namespace Drupal\designs;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an interface for design settings.
 */
interface DesignSettingInterface extends PluginInspectionInterface, DerivativeInspectionInterface, ConfigurableInterface, DependentPluginInterface, PluginFormInterface {

  /**
   * Get the label of the setting.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The label.
   */
  public function label();

  /**
   * Get the description of the setting.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The description.
   */
  public function getDescription();

  /**
   * Check the setting is required.
   *
   * @return bool
   *   The result.
   */
  public function isRequired();

  /**
   * Get the a definition value for the setting.
   *
   * @param string $key
   *   The definition key.
   * @param mixed $default
   *   The default value when not available.
   *
   * @return mixed
   *   The value.
   */
  public function getDefinitionValue($key, $default);

  /**
   * Build the setting element.
   *
   * @param array $element
   *   The design render element.
   *
   * @return array
   *   The render element.
   */
  public function build(array &$element);

  /**
   * Create the render array for the setting.
   *
   * @param array $build
   *   The build array from the content generation.
   * @param array $element
   *   The design render element.
   *
   * @return array|null
   *   The setting render array, or nothing.
   */
  public function process(array $build, array &$element);

  /**
   * Get the used source keys.
   *
   * @return string[]
   *   The used sources by key.
   */
  public function getUsedSources();

  /**
   * The design content for the setting.
   *
   * @return \Drupal\designs\DesignContentInterface|null
   *   The design content plugin.
   */
  public function getContent();

  /**
   * Get the design definition.
   *
   * @return array
   *   The definition.
   */
  public function getDesignDefinition();

  /**
   * Set the design definition.
   *
   * @param array $definition
   *   The definition from the layout.
   *
   * @return $this
   *   The object instance.
   */
  public function setDesignDefinition(array $definition);

  /**
   * Build the form for the design setting.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state);

  /**
   * Validation of ::buildForm().
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateForm(array $form, FormStateInterface $form_state);

  /**
   * Submission of ::buildForm().
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The value.
   */
  public function submitForm(array $form, FormStateInterface $form_state);

}
