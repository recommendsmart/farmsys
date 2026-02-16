<?php

namespace Drupal\subgroup;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Manages adding and removing the fields for subgroup metadata.
 */
class SubgroupFieldManager implements SubgroupFieldManagerInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The fields this manager takes care of.
   *
   * Keys are field names, values are field labels.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup[]
   */
  protected $fields;

  /**
   * Constructs a new SubgroupFieldManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
    $this->fields = [
      SUBGROUP_DEPTH_FIELD => $this->t('Depth'),
      SUBGROUP_LEFT_FIELD => $this->t('Left'),
      SUBGROUP_RIGHT_FIELD => $this->t('Right'),
      SUBGROUP_TREE_FIELD => $this->t('Tree'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function installFields($group_type_id) {
    $fsc_storage = $this->entityTypeManager->getStorage('field_storage_config');
    $fc_storage = $this->entityTypeManager->getStorage('field_config');

    foreach ($this->fields as $field_name => $field_label) {
      $field_storage = $fsc_storage->load("group.$field_name");
      $field = $fc_storage->load("group.$group_type_id.$field_name");
      if (!empty($field)) {
        throw new \RuntimeException(sprintf('The field "%s" already exists on group type "%s".', $field_name, $group_type_id));
      }
      $fc_storage->create([
        'field_storage' => $field_storage,
        'bundle' => $group_type_id,
        'label' => $field_label,
      ])->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteFields($group_type_id) {
    $fc_storage = $this->entityTypeManager->getStorage('field_config');

    foreach ($this->fields as $field_name => $field_label) {
      $field = $fc_storage->load("group.$group_type_id.$field_name");
      if (empty($field)) {
        throw new \RuntimeException(sprintf('The field "%s" does not exist on group type "%s".', $field_name, $group_type_id));
      }
      $field->delete();
    }
  }

}
