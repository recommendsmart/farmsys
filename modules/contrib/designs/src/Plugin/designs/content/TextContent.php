<?php

namespace Drupal\designs\Plugin\designs\content;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\designs\DesignContentBase;

/**
 * The text content plugin.
 *
 * @DesignContent(
 *   id = "text",
 *   label = @Translation("Text"),
 * )
 */
class TextContent extends DesignContentBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'value' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text'),
      '#default_value' => $this->configuration['value'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array &$element) {
    return [
      '#markup' => Markup::create($this->configuration['value']),
    ];
  }

}
