<?php

namespace Drupal\designs_test\Plugin\designs\setting;

use Drupal\Core\Form\FormStateInterface;
use Drupal\designs\DesignSettingBase;

/**
 * The test setting.
 *
 * @DesignSetting(
 *   id = "test_setting",
 *   label = @Translation("Test Setting")
 * )
 */
class TestSetting extends DesignSettingBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'test_local' => '',
      'test_global' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['test_global'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test Global'),
      '#default_value' => $this->configuration['test_global'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    if (!empty($values['test_global'])  && $values['test_global'] === 'fail') {
      $form_state->setError($form['test_global'], $this->t('Test global was a failure.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    if (!empty($values['test_global'])) {
      $values['test_global'] .= '-test';
      $form_state->setValue($values, $form['#parents']);
    }
    $this->setConfiguration($values);
    return $this->getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $values = $form_state->getValue($form['#parents']);
    $form['test_local'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test Local'),
      '#default_value' => $values['test_local'] ?? $this->configuration['test_local'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    if (!empty($values['test_local']) && $values['test_local'] === 'fail') {
      $form_state->setError($form['test_local'], $this->t('Test local was a failure.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    if (!empty($values['test_local'])) {
      $values['test_local'] .= '-test';
      $form_state->setValue($values, $form['#parents']);
    }
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildSetting(array &$element) {
    return ['#markup' => $this->configuration['test_local']];
  }

}
