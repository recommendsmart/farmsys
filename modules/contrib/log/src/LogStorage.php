<?php

namespace Drupal\log;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Defines the controller class for logs.
 *
 * This extends the base storage class, adding required special handling for
 * log entities.
 */
class LogStorage extends SqlContentEntityStorage {

  /**
   * {@inheritdoc}
   */
  protected function doPostSave(EntityInterface $entity, $update) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */

    if ($update && $this->entityType->isTranslatable()) {
      $this->invokeTranslationHooks($entity);
    }

    // Get the log's current name.
    $current_name = $entity->get('name')->value;

    // We will automatically set the log name under two conditions:
    // 1. Saving new/existing logs without a name.
    // 2. Updating existing logs that were saved using the naming pattern.
    $set_name = FALSE;
    if (empty($current_name)) {
      $set_name = TRUE;
    }
    elseif ($update && !empty($entity->original)) {

      // Generate a log name using the original entity.
      $original_generated_name = $this->generateLogName($entity->original);

      // Compare the current log name to what would have been the original
      // auto-generated name, to determine if the name was auto-generated
      // previously. If it was, we will regenerate it.
      if ($current_name == $original_generated_name) {
        $set_name = TRUE;
      }
    }

    // We must run the parent method before we set the name, so that new logs
    // have an ID that can be used in token replacements.
    // Also, we must run the parent method after the logic above, because the
    // parent method unsets $entity->original.
    parent::doPostSave($entity, $update);

    // Set the log name, if necessary.
    if ($set_name) {

      // Generate a new name.
      $new_name = $this->generateLogName($entity);

      // If the name has been changed, update the entity.
      if ($current_name != $new_name) {
        $entity->set('name', $new_name);
        $entity->save();
      }
    }
  }

  /**
   * Helper method for generating a log name.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The log entity.
   *
   * @return string
   *   Returns the generated log name.
   */
  protected function generateLogName(EntityInterface $entity) {

    // Get the log type's naming pattern.
    $name_pattern = $entity->getTypeNamePattern();

    // Pass in an empty bubbleable metadata object, so we can avoid starting a
    // renderer, for example if this happens in a REST resource creating
    // context.
    return \Drupal::token()->replace(
      $name_pattern,
      ['log' => $entity],
      [],
      new BubbleableMetadata()
    );
  }

}
