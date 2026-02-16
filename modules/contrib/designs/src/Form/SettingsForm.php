<?php

namespace Drupal\designs\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides the form managing the settings plugins for a design.
 */
class SettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  protected function getTitle() {
    return $this->t('Settings');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $design = $this->getDesign();
    $definition = $design->getPluginDefinition();

    // Cycle through all the defined settings creating the form.
    foreach ($definition->getSettings() as $setting_id => $setting) {
      $parents = array_merge($form['#parents'], [$setting_id]);

      $plugin = $design->getSetting($setting_id);
      $form[$setting_id] = self::getChildElement($parents, $form) + [
        '#design' => $design,
        '#setting' => $plugin,
      ];
      $form[$setting_id] = $plugin->buildConfigurationForm($form[$setting_id], $form_state);
    }

    // Remove details when no settings defined.
    if (!isset($setting)) {
      unset($form['#type']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    foreach (Element::children($form) as $child) {
      if (isset($form[$child]['#setting'])) {
        $form[$child]['#setting']->validateConfigurationForm($form[$child], $form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array $form, FormStateInterface $form_state) {
    $result = [];
    foreach (Element::children($form) as $child) {
      if (isset($form[$child]['#setting'])) {
        $result[$child] = $form[$child]['#setting']->submitConfigurationForm($form[$child], $form_state);
      }
    }
    $form_state->setValue($form['#parents'], $result);
    return $result;
  }

}
