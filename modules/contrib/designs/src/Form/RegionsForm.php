<?php

namespace Drupal\designs\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the design region form.
 */
class RegionsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  protected function getTitle() {
    return $this->t('Regions');
  }

  /**
   * {@inheritdoc}
   */
  protected function getPlugins(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginManager(): PluginManagerInterface {
    return $this->contentManager;
  }

  /**
   * Build the region form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    unset($form['#type']);

    // Get the areas that the source content can be placed within.
    $design = $this->design;
    foreach ($design->getRegions() as $region_id => $region) {
      $parents = array_merge($form['#parents'], [$region_id]);

      // Create the details for the region.
      $form[$region_id] = self::getChildElement($parents, $form);
      $form_handler = new RegionForm(
        $this->manager,
        $this->settingManager,
        $this->contentManager
      );
      $form[$region_id] = $form_handler
        ->setDesign($this->design)
        ->setRegion($region)
        ->buildForm($form[$region_id], $form_state);
    }

    return $form;
  }

}
