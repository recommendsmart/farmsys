<?php

namespace Drupal\designs\Plugin\designs\setting;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\designs\DesignSettingBase;

/**
 * The uri setting.
 *
 * @DesignSetting(
 *   id = "uri",
 *   label = @Translation("Uri")
 * )
 */
class UriSetting extends DesignSettingBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);

    $form['absolute'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Absolute'),
      '#description' => $this->t('Ensure the URL is absolute.'),
      '#default_value' => $values['absolute'] ?? $this->configuration['absolute'],
    ];

    $form['default'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default'),
      '#description' => $this->t('The default URL to use.'),
      '#default_value' => $values['default'] ?? $this->configuration['default'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'uri' => $this->getDefinitionValue('default_value', ''),
      'default' => $this->getDefinitionValue('default', ''),
      'absolute' => $this->getDefinitionValue('absolute', TRUE),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function buildSetting(array &$element) {
    return ['#markup' => $this->configuration['uri']];
  }

  /**
   * Get the converted URL from value.
   *
   * @param string $value
   *   The value.
   *
   * @return string
   *   The URL.
   */
  protected function getUrl($value) {
    // Use standard drupal uri first.
    try {
      $url = Url::fromUri($value)->toString();
    }
    catch (\Exception $e) {
      // Use user input uri second.
      try {
        $url = Url::fromUserInput($value)->toString();
      }
      catch (\Exception $e) {
        $url = '';
      }
    }
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function process(array $build, &$element) {
    // When there is no setting content, we use the provided values.
    $output = $this->renderer->render($build);

    // Process the rendered content.
    $url = $this->getUrl($output);
    if ($url) {
      return ['#plain_text' => $url];
    }

    // Use default value otherwise.
    return ['#plain_text' => $this->getUrl($this->configuration['default'])];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);

    $form['uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Uri'),
      '#description' => $this->getDescription(),
      '#default_value' => $values['uri'] ?? $this->configuration['uri'],
      '#required' => $this->isRequired(),
    ];

    return $form;
  }

}
