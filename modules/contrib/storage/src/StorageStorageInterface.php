<?php

namespace Drupal\storage;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface StorageStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Storage revision IDs for a specific Storage.
   *
   * @param \Drupal\storage\Entity\StorageInterface $entity
   *   The Storage entity.
   *
   * @return int[]
   *   Storage revision IDs (in ascending order).
   */
  public function revisionIds(StorageInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Storage author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Storage revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\storage\Entity\StorageInterface $entity
   *   The Storage entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(StorageInterface $entity);

  /**
   * Unsets the language for all Storage with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
