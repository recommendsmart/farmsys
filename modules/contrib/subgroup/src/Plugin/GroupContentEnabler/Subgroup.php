<?php

namespace Drupal\subgroup\Plugin\GroupContentEnabler;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a content enabler for users.
 *
 * @GroupContentEnabler(
 *   id = "subgroup",
 *   label = @Translation("Subgroup"),
 *   description = @Translation("Adds groups to groups as subgroups."),
 *   entity_type_id = "group",
 *   pretty_path_key = "subgroup",
 *   reference_label = @Translation("Group"),
 *   reference_description = @Translation("The group you want to make a subgroup"),
 *   deriver = "Drupal\subgroup\Plugin\GroupContentEnabler\SubgroupDeriver",
 *   code_only = TRUE,
 *   handlers = {
 *     "access" = "Drupal\subgroup\Plugin\SubgroupAccessControlHandler",
 *     "permission_provider" = "Drupal\subgroup\Plugin\SubgroupPermissionProvider",
 *   },
 * )
 */
class Subgroup extends GroupContentEnablerBase {

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $operations = [];

    $plugin_id = $this->getPluginId();
    $group_type_id = $this->getEntityBundle();
    $group_type = \Drupal::entityTypeManager()->getStorage('group_type')->load($group_type_id);

    /** @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface $manager */
    $manager = \Drupal::service('plugin.manager.group_content_enabler');
    $create_permission = $manager->getPermissionProvider($plugin_id)->getEntityCreatePermission();

    if ($group->hasPermission($create_permission, \Drupal::currentUser())) {
      $route_params = ['group' => $group->id(), 'plugin_id' => $plugin_id];
      $operations["subgroup-create-$group_type_id"] = [
        'title' => $this->t('Add @group_type subgroup', ['@group_type' => $group_type->label()]),
        'url' => new Url('entity.group_content.create_form', $route_params),
        'weight' => 30,
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations() {
    $operations['subgroup-settings'] = [
      'title' => $this->t('Subgroup settings'),
      'url' => new Url('subgroup.settings'),
      'weight' => 99,
    ];

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['group_cardinality'] = 1;
    $config['entity_cardinality'] = 1;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Disable the group cardinality field as the functionality of this module
    // relies on a cardinality of 1. We don't just hide it, though, to keep a UI
    // that's consistent with other content enabler plugins.
    $info = $this->t("This field has been disabled by the plugin to guarantee the functionality that's expected of it.");
    $form['group_cardinality']['#disabled'] = TRUE;
    $form['group_cardinality']['#description'] .= '<br /><em>' . $info . '</em>';

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
    $dependencies['config'][] = 'group.type.' . $this->getEntityBundle();
    return $dependencies;
  }

}
