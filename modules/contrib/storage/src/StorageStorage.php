<?php

namespace Drupal\storage;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\storage\Entity\StorageInterface;

/**
 * Defines the storage handler class for Storage entities.
 *
 * This extends the base storage class, adding required special handling for
 * Storage entities.
 *
 * @ingroup storage
 */
class StorageStorage extends SqlContentEntityStorage implements StorageStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(StorageInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {storage_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {storage_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(StorageInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {storage_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('storage_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
