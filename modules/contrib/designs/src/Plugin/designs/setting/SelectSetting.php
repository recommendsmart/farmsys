<?php

namespace Drupal\designs\Plugin\designs\setting;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\designs\DesignSettingBase;

/**
 * The select setting.
 *
 * @DesignSetting(
 *   id = "select",
 *   label = @Translation("Select")
 * )
 */
class SelectSetting extends DesignSettingBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'value' => $this->getDefinitionValue('default_value', ''),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function buildSetting(array &$element) {
    return ['#markup' => $this->configuration['value']];
  }

  /**
   * Get the options available for the select.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   The labels.
   */
  protected function getOptions() {
    $options = [
      '' => $this->getDefinitionValue('empty', '- None -'),
    ] + (array) $this->getDefinitionValue('options', []);

    return array_map(function ($string) {
      return new TranslatableMarkup($string);
    }, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function process(array $build, &$element) {
    // When there is no setting content, we use the provided values.
    $output = $this->renderer->render($build);

    // Ensure only the selection can be used.
    $options = $this->getOptions();
    if (isset($options[(string) $output])) {
      return ['#markup' => $output];
    }

    // Option is not available so use default value.
    return ['#markup' => $this->getDefinitionValue('default_value', '')];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);

    $form['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Select'),
      '#description' => $this->getDescription(),
      '#default_value' => $values['value'] ?? $this->configuration['value'],
      '#required' => $this->isRequired(),
      '#options' => $this->getOptions(),
    ];

    return $form;
  }

}
