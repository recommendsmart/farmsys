<?php

namespace Drupal\group_field_bundle\Plugin\GroupContentEnabler;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_bundle\Entity\FieldBundleConfig;

/**
 * Provides a content enabler for nodes.
 *
 * @GroupContentEnabler(
 *   id = "group_field_bundle",
 *   label = @Translation("Group field bundle"),
 *   description = @Translation("Adds field bundles to groups both publicly and privately."),
 *   entity_type_id = "field_bundle",
 *   entity_access = TRUE,
 *   reference_label = @Translation("Label"),
 *   reference_description = @Translation("The label of the field bundle to add to the group"),
 *   deriver = "Drupal\group_field_bundle\Plugin\GroupContentEnabler\GroupFieldBundleDeriver",
 *   handlers = {
 *     "access" = "Drupal\group\Plugin\GroupContentAccessControlHandler",
 *     "permission_provider" = "Drupal\group\Plugin\GroupContentPermissionProvider",
 *   }
 * )
 */
class GroupFieldBundle extends GroupContentEnablerBase {

  /**
   * Retrieves the field bundle config this plugin supports.
   *
   * @return \Drupal\field_bundle\FieldBundleConfigInterface
   *   The field bundle config this plugin supports.
   */
  protected function getFieldBundleConfig() {
    return FieldBundleConfig::load($this->getEntityBundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $account = \Drupal::currentUser();
    $plugin_id = $this->getPluginId();
    $type = $this->getEntityBundle();
    $operations = [];

    if ($group->hasPermission("create $plugin_id entity", $account)) {
      $route_params = ['group' => $group->id(), 'plugin_id' => $plugin_id];
      $operations["group_field_bundle-create-$type"] = [
        'title' => $this->t('Add @type', ['@type' => $this->getFieldbundleConfig()->label()]),
        'url' => new Url('entity.group_content.create_form', $route_params),
        'weight' => 40,
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['entity_cardinality'] = 1;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Disable the entity cardinality field as the functionality of this module
    // relies on a cardinality of 1. We don't just hide it, though, to keep a UI
    // that's consistent with other content enabler plugins.
    $info = $this->t("This field has been disabled by the plugin to guarantee the functionality that's expected of it.");
    $form['entity_cardinality']['#disabled'] = TRUE;
    $form['entity_cardinality']['#description'] .= '<br /><em>' . $info . '</em>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $dependencies['config'][] = 'field_bundle.config.' . $this->getEntityBundle();
    return $dependencies;
  }

}
