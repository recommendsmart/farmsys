<?php

namespace Drupal\basket\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * Defines a wizard for the basket table.
 *
 * @ViewsWizard(
 *   id = "basket",
 *   module = "basket",
 *   base_table = "basket",
 *   title = @Translation("Basket")
 * )
 */
class Basket extends WizardPluginBase {

  /**
   * Set the add_time column.
   *
   * @var string
   */
  protected $createdColumn = 'add_time';

  /**
   * {@inheritdoc}
   */
  protected function defaultDisplayOptions() {
    $display_options = parent::defaultDisplayOptions();

    // Add permission-based access control.
    $display_options['access']['type'] = 'perm';
    $display_options['access']['options']['perm'] = 'basket add_button_access';

    return $display_options;
  }

}
