<?php

namespace Drupal\designs_test\Plugin\designs\content;

use Drupal\Core\Form\FormStateInterface;
use Drupal\designs\DesignContentBase;

/**
 * The test content plugin.
 *
 * @DesignContent(
 *   id = "test_content",
 *   label = @Translation("Test content")
 * )
 */
class TestContent extends DesignContentBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'test1' => '',
      'test2' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['test1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test 1'),
      '#default_value' => $this->configuration['test1'],
    ];

    $form['test2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test 2'),
      '#default_value' => $this->configuration['test2'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    if (!empty($values['test1']) && $values['test1'] === 'fail') {
      $form_state->setError($form['test1'], $this->t('Test 1 was a failure.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    if (!empty($values['test2'])) {
      $values['test2'] .= '-test';
      $form_state->setValue($form['#parents'], $values);
    }
    return parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function build(array &$element) {
    return [
      '#markup' => $this->configuration['test1'],
    ];
  }

}
