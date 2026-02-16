<?php

namespace Drupal\properties_field\PropertiesValueType;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for properties value type plugins.
 */
abstract class PropertiesValueTypeBase extends PluginBase implements PropertiesValueTypeInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(array $form, FormStateInterface $form_state, array &$complete_form) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsSummary() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function widgetForm(array $element, $value, FormStateInterface $form_state) {
    $element['#type'] = 'textfield';
    $element['#default_value'] = $value;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formatterSettingsForm(array $form, FormStateInterface $form_state, array &$complete_form) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function formatterSettingsSummary() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function formatterRender($value) {
    return $value;
  }

}
