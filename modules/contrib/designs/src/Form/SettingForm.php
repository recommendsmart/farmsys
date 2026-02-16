<?php

namespace Drupal\designs\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\designs\DesignSettingInterface;

/**
 * Provides the setting plugin form.
 */
class SettingForm extends ContentForm {

  /**
   * The design setting.
   *
   * @var \Drupal\designs\DesignSettingInterface
   */
  protected $setting;

  /**
   * Get the design setting.
   *
   * @return \Drupal\designs\DesignSettingInterface
   *   The design setting.
   */
  public function getSetting(): DesignSettingInterface {
    return $this->setting;
  }

  /**
   * Set the design setting.
   *
   * @param \Drupal\designs\DesignSettingInterface $setting
   *   The design setting.
   *
   * @return $this
   *   The object instance.
   */
  public function setSetting(DesignSettingInterface $setting) {
    $this->setting = $setting;
    $this->setContent($this->setting->getContent());
    return $this;
  }

  /**
   * Get the content definitions restricted by setting.
   *
   * @return array[]
   *   The definitions.
   */
  protected function getContents() {
    return [
      '' => ['label' => $this->t('Setting')],
    ] + $this->contentManager->getSourceDefinitions(
      'settings',
      $this->design->getSourcePlugin()->getPluginId()
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getTitle() {
    return $this->setting->label();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    unset($form['label']);
    $form['type'] = [
      '#type' => 'hidden',
      '#value' => $this->setting->getPluginId(),
      '#weight' => -100,
    ];

    // When no content plugin defined, use the setting builtin form.
    if (!$this->content) {
      $form = $this->setting->buildForm($form, $form_state);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    if ($values['plugin'] === $form['plugin']['#default_value']) {
      if ($values['plugin']) {
        parent::validateForm($form, $form_state);
      }
      else {
        $this->setting->validateForm($form, $form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array $form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $values = $form_state->getValue($form['#parents']);
    if ($values && $values['plugin']) {
      $values = [
        'type' => $values['type'],
      ] + $values;
    }
    else {
      $values = $this->setting->submitForm($form, $form_state);
    }
    $form_state->setValue($form['#parents'], $values ?: []);
    return $values;
  }

}
