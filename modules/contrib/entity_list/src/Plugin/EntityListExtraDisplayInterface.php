<?php

namespace Drupal\entity_list\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Entity list extra display plugins.
 */
interface EntityListExtraDisplayInterface extends PluginInspectionInterface {

  /**
   * Generates the display settings form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The display settings form.
   */
  public function settingsForm(FormStateInterface $form_state);
}
