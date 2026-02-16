<?php

/**
 * @file
 * Contains \Drupal\resource\ResourceStorage.
 */

namespace Drupal\resource;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Defines the controller class for resources.
 *
 * This extends the base storage class, adding required special handling for
 * resource entities.
 */
class ResourceStorage extends SqlContentEntityStorage implements ResourceStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(ResourceInterface $resource) {
    return $this->database->query(
      'SELECT vid FROM {resource_revision} WHERE id=:id ORDER BY vid',
      array(':id' => $resource->id())
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {resource_field_revision} WHERE uid = :uid ORDER BY vid',
      array(':uid' => $account->id())
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(ResourceInterface $resource) {
    return $this->database->query('SELECT COUNT(*) FROM {resource_field_revision} WHERE id = :id AND default_langcode = 1', array(':id' => $resource->id()))->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function updateType($old_type, $new_type) {
    return $this->database->update('resource')
      ->fields(array('type' => $new_type))
      ->condition('type', $old_type)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('resource_revision')
      ->fields(array('langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED))
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
