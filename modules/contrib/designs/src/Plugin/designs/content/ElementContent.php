<?php

namespace Drupal\designs\Plugin\designs\content;

use Drupal\Core\Form\FormStateInterface;
use Drupal\designs\DesignContentBase;

/**
 * The element content plugin.
 *
 * @DesignContent(
 *   id = "element",
 *   label = @Translation("Content"),
 *   content = FALSE
 * )
 */
class ElementContent extends DesignContentBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'element' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $design = $form['#design'];

    $form['element'] = [
      '#type' => 'select',
      '#title' => $this->t('Content'),
      '#options' => [
        '' => $this->t('- No content -'),
      ] + $design->getSources(),
      '#default_value' => $this->configuration['element'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array &$element) {
    $value = $this->configuration['element'];

    if (isset($element[$value])) {
      return $element[$value];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getUsedSources() {
    $content = [];
    if (!empty($this->configuration['element'])) {
      $content[] = $this->configuration['element'];
    }
    return $content;
  }

}
