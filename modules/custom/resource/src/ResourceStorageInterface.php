<?php

/**
 * @file
 * Contains \Drupal\resource\ResourceStorageInterface.
 */

namespace Drupal\resource;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for resource entity storage classes.
 */
interface ResourceStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of resource revision IDs for a specific resource.
   *
   * @param \Drupal\resource\ResourceInterface
   *   The resource entity.
   *
   * @return int[]
   *   Resource revision IDs (in ascending order).
   */
  public function revisionIds(ResourceInterface $resource);

  /**
   * Gets a list of revision IDs having a given user as resource author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Resource revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\resource\ResourceInterface
   *   The resource entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ResourceInterface $resource);

  /**
   * Updates all resources of one type to be of another type.
   *
   * @param string $old_type
   *   The current resource type of the resources.
   * @param string $new_type
   *   The new resource type of the resources.
   *
   * @return int
   *   The number of resources whose resource type field was modified.
   */
  public function updateType($old_type, $new_type);

  /**
   * Unsets the language for all resources with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *  The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);
}
