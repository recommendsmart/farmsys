<?php

namespace Drupal\entity_extra_field\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Define entity extra field interface.
 */
interface EntityExtraFieldInterface extends ConfigEntityInterface {

  /**
   * Get extra field machine name.
   *
   * @return string
   *   The extra field machine name.
   */
  public function name(): ?string;

  /**
   * Get the extra field description.
   *
   * @return string
   *   The extra field description.
   */
  public function description(): ?string;

  /**
   * Should display the extra field label.
   *
   * @return bool
   *   Return TRUE if the field label should be rendered; otherwise FALSE.
   */
  public function displayLabel(): bool;

  /**
   * Get extra field display.
   *
   * @return array
   *   An array of display information.
   */
  public function getDisplay(): array;

  /**
   * Get extra field display type.
   *
   * @return string
   *   Get the display type.
   */
  public function getDisplayType(): ?string;

  /**
   * Get field type plugin label.
   *
   * @return string
   *   The field type plugin label.
   */
  public function getFieldTypeLabel(): string;

  /**
   * Get field type plugin identifier.
   *
   * @return string
   *   The field type plugin identifier.
   */
  public function getFieldTypePluginId(): string;

  /**
   * Get field type plugin configuration.
   *
   * @return array
   *   An array of the plugin configuration.
   */
  public function getFieldTypePluginConfig(): array;

  /**
   * Get field type condition.
   *
   * @return array
   *   An array of condition plugin with configuration.
   */
  public function getFieldTypeCondition(): array;

  /**
   * Get field type conditions all pass.
   *
   * @return bool
   *   Return TRUE if all field type conditions need to pass; otherwise FALSE.
   */
  public function getFieldTypeConditionsAllPass(): bool;

  /**
   * Get base entity type id.
   *
   * @return string
   *   The base entity type identifier.
   */
  public function getBaseEntityTypeId(): string;

  /**
   * Get base bundle type id.
   *
   * @return string
   *   A base bundle type id.
   */
  public function getBaseBundleTypeId(): ?string;

  /**
   * Get base entity type instance.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   *   The entity type instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getBaseEntityType(): EntityTypeInterface;

  /**
   * Get base entity type bundle instance.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   *   The entity type bundle instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getBaseEntityTypeBundle(): EntityTypeInterface;

  /**
   * Get the base entity context.
   *
   * @return \Drupal\Core\Plugin\Context\EntityContext
   *   The entity context.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getBaseEntityContext(): EntityContext;

  /**
   * Get the cache discovery identifier.
   *
   * @return string
   *   The cache identifier in the cache_discovery table.
   */
  public function getCacheDiscoveryId(): string;

  /**
   * Get the cache render tag.
   *
   * @return string
   *   The cache render tag.
   */
  public function getCacheRenderTag(): string;

  /**
   * Get active field type conditions.
   *
   * @return array
   *   An array of active field type conditions.
   */
  public function getActiveFieldTypeConditions(): array;

  /**
   * Get the build attachments.
   *
   * @return array
   *   An array of the build attachments.
   */
  public function getBuildAttachments(): array;

  /**
   * Set a build attachment.
   *
   * @param string $type
   *   The type of attachment (library, drupalSettings, etc)
   * @param array $attachment
   *   An array of attachment settings for the particular type.
   */
  public function setBuildAttachment(string $type, array $attachment);

  /**
   * Check if entity identifier exist.
   *
   * @param string $name
   *   The entity machine name.
   *
   * @return bool
   *   Return TRUE if machine name exist; otherwise FALSE.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function exists(string $name): bool;

  /**
   * Build the extra field.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity type the extra field is being attached too.
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The entity display the extra field is apart of.
   *
   * @return array
   *   The extra field renderable array.
   */
  public function build(
    EntityInterface $entity,
    EntityDisplayInterface $display
  ): array;

  /**
   * Extra field has display component.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   An entity display to check for the extra field.
   *
   * @return bool
   *   Return TRUE if the component exists in the display; otherwise FALSE.
   */
  public function hasDisplayComponent(EntityDisplayInterface $display): bool;

  /**
   * Has extra field conditions been met.
   *
   * @param array $contexts
   *   An array of context values.
   * @param bool $all_must_pass
   *   Determine if all conditions must pass.
   *
   * @return bool
   *   Return TRUE if the extra field conditions have been met; otherwise FALSE.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function hasConditionsBeenMet(
    array $contexts,
    bool $all_must_pass = FALSE
  ): bool;

}
