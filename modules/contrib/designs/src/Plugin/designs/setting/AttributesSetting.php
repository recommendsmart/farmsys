<?php

namespace Drupal\designs\Plugin\designs\setting;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\Attribute;
use Drupal\designs\DesignSettingBase;

/**
 * The attributes setting.
 *
 * @DesignSetting(
 *   id = "attributes",
 *   label = @Translation("Attributes")
 * )
 */
class AttributesSetting extends DesignSettingBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);

    $form['existing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include existing attributes'),
      '#description' => $this->t('Allow existing attributes on the element to be included.'),
      '#default_value' => $values['existing'] ?? $this->configuration['existing'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'attributes' => $this->getDefinitionValue('default_value', ''),
      'existing' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build(array &$element) {
    if (empty($this->configuration['existing'])) {
      $element['#attributes'] = [];
    }

    return parent::build($element);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildSetting(array &$element) {
    return ['#markup' => $this->configuration['attributes']];
  }

  /**
   * {@inheritdoc}
   */
  public function process(array $build, &$element) {
    // Get any existing element attributes.
    $attributes = $element['#attributes'] ?? [];

    // When there is no setting content, we use the provided values.
    $output = $this->renderer->render($build);

    // Process the rendered value into the attributes array.
    $parse_html = '<div ' . $output . '></div>';
    foreach (Html::load($parse_html)->getElementsByTagName('div') as $div) {
      foreach ($div->attributes as $attr) {
        if (isset($attributes[$attr->nodeName])) {
          $attributes[$attr->nodeName] .= ' ' . trim($attr->nodeValue);
        }
        else {
          $attributes[$attr->nodeName] = trim($attr->nodeValue);
        }
      }
    }

    // Update the element attributes for display.
    return [
      '#attributes' => new Attribute($attributes),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);

    $form['attributes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Attributes'),
      '#description' => $this->getDescription(),
      '#default_value' => $values['attributes'] ?? $this->configuration['attributes'],
      '#required' => $this->isRequired(),
    ];

    return $form;
  }

}
