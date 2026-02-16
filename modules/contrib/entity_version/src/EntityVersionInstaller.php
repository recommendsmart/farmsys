<?php

declare(strict_types = 1);

namespace Drupal\entity_version;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Handles the installation of the entity version field on bundles.
 */
class EntityVersionInstaller implements EntityVersionInstallerInterface {

  /**
   * The field config.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $fieldConfig;

  /**
   * The field storage config.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorageConfig;

  /**
   * EntityVersionInstaller constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->fieldConfig = $entityTypeManager->getStorage('field_config');
    $this->fieldStorageConfig = $entityTypeManager->getStorage('field_storage_config');
  }

  /**
   * {@inheritdoc}
   */
  public function install(string $entity_type = 'node', array $bundles = [], array $default_value = []): void {
    if (!$this->fieldStorageConfig->load("$entity_type.version")) {
      $this->fieldStorageConfig->create([
        'field_name' => 'version',
        'entity_type' => $entity_type,
        'type' => 'entity_version',
      ])->save();
    }

    foreach ($bundles as $bundle) {
      if ($this->fieldConfig->load("$entity_type." . $bundle . '.version')) {
        continue;
      }
      $this->fieldConfig->create([
        'entity_type' => $entity_type,
        'field_name' => 'version',
        'bundle' => $bundle,
        'label' => t('Version'),
        'cardinality' => 1,
        'translatable' => FALSE,
        'default_value' => empty($default_value) ? $default_value : [$default_value],
      ])->save();
    }
  }

}
