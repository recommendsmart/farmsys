<?php

declare(strict_types = 1);

namespace Drupal\entity_version\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\entity_version\EntityVersionSettingsInterface;
use Drupal\field\FieldConfigInterface;

/**
 * Defines the EntityVersionSettings entity type.
 *
 * @ConfigEntityType(
 *   id = "entity_version_settings",
 *   handlers = {
 *     "storage" = "Drupal\entity_version\Entity\EntityVersionSettingsStorage",
 *   },
 *   label = @Translation("Entity Version Settings"),
 *   label_collection = @Translation("Entity Version Settings"),
 *   label_singular = @Translation("entity version setting"),
 *   label_plural = @Translation("entity version settings"),
 *   label_count = @PluralTranslation(
 *     singular = "@count entity version setting",
 *     plural = "@count entity version settings",
 *   ),
 *   admin_permission = "administer entity version",
 *   config_prefix = "settings",
 *   entity_keys = {
 *     "id" = "id"
 *   },
 *   config_export = {
 *     "id",
 *     "target_entity_type_id",
 *     "target_bundle",
 *     "target_field",
 *   }
 * )
 */
class EntityVersionSettings extends ConfigEntityBase implements EntityVersionSettingsInterface {

  /**
   * The id. Combination of $target_entity_type_id.$target_bundle.
   *
   * @var string
   */
  protected $id;

  /**
   * The entity type ID (machine name).
   *
   * @var string
   */
  protected $target_entity_type_id;

  /**
   * The bundle (machine name).
   *
   * @var string
   */
  protected $target_bundle;

  /**
   * The target field (machine name).
   *
   * @var string
   */
  protected $target_field;

  /**
   * Constructs a EntityVersionSettings object.
   *
   * @param array $values
   *   An array of the referring entity bundle with:
   *   - target_entity_type_id: The entity type.
   *   - target_bundle: The bundle.
   *   Other array elements will be used to set the corresponding properties on
   *   the class; see the class property documentation for details.
   * @param string $entity_type
   *   The entity type id.
   */
  public function __construct(array $values, string $entity_type = 'entity_version_settings') {
    if (empty($values['target_entity_type_id'])) {
      throw new \InvalidArgumentException('Attempt to create entity version settings without a target_entity_type_id.');
    }
    if (empty($values['target_bundle'])) {
      throw new \InvalidArgumentException('Attempt to create entity version settings without a target_bundle.');
    }
    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.ShortMethodName)
   */
  public function id(): string {
    return $this->target_entity_type_id . '.' . $this->target_bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeId(): string {
    return $this->target_entity_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetEntityTypeId(string $target_entity_type_id): EntityVersionSettingsInterface {
    $this->target_entity_type_id = $target_entity_type_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundle(): string {
    return $this->target_bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetBundle(string $target_bundle): EntityVersionSettingsInterface {
    $this->target_bundle = $target_bundle;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetField(): string {
    return $this->target_field;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetField(string $target_field): EntityVersionSettingsInterface {
    $this->target_field = $target_field;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    $this->id = $this->id();
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): EntityVersionSettingsInterface {
    parent::calculateDependencies();

    // Create dependency on the bundle.
    $entity_type = \Drupal::entityTypeManager()->getDefinition($this->target_entity_type_id);
    $bundle_config_dependency = $entity_type->getBundleConfigDependency($this->target_bundle);

    $this->addDependency($bundle_config_dependency['type'], $bundle_config_dependency['name']);

    if (!empty($this->target_field)) {
      $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($this->target_entity_type_id, $this->target_bundle);
      $target_field = $field_definitions[$this->target_field];

      // If the target field is a field config, then add the dependency to the
      // configuration id. For field base definitions we have the dependency
      // for the entity type and bundle above.
      if ($target_field instanceof FieldConfigInterface) {
        $this->addDependency($target_field->getConfigDependencyKey(), $target_field->getConfigDependencyName());
      }
    }

    return $this;
  }

}
