<?php

namespace Drupal\group_storage\Plugin\GroupContentEnabler;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\storage\Entity\StorageType;
use Drupal\storage\Entity\StorageTypeInterface;

/**
 * Provides a content enabler for storage entities.
 *
 * @GroupContentEnabler(
 *   id = "group_storage",
 *   label = @Translation("Group storage"),
 *   description = @Translation("Adds storage entities to groups both publicly
 *   and privately."), entity_type_id = "storage", entity_access = TRUE,
 *   reference_label = @Translation("Title"), reference_description =
 *   @Translation("The title of the storage to add to the group"), deriver =
 *   "Drupal\group_storage\Plugin\GroupContentEnabler\GroupStorageDeriver",
 *   handlers = {
 *     "access" = "Drupal\group\Plugin\GroupContentAccessControlHandler",
 *     "permission_provider" =
 *   "Drupal\group_storage\Plugin\GroupStoragePermissionProvider",
 *   }
 * )
 */
class GroupStorage extends GroupContentEnablerBase {

  /**
   * Retrieves the storage type this plugin supports.
   *
   * @return StorageTypeInterface
   *   The storage type this plugin supports.
   */
  protected function getStorageType() {
    return StorageType::load($this->getEntityBundle());
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
      $operations["group_storage-create-$type"] = [
        'title' => $this->t('Add @type', [
          '@type' => $this->getStorageType()
            ->label(),
        ]),
        'url' => new Url('entity.group_content.create_form', $route_params),
        'weight' => 30,
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
    $dependencies['config'][] = 'storage.storage_type.' . $this->getEntityBundle();
    return $dependencies;
  }

}
