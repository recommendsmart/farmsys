<?php

namespace Drupal\element;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\element\Entity\ElementInterface;

/**
 * Defines the storage handler class for element entities.
 *
 * This extends the base storage class, adding required special handling for
 * element entities.
 *
 * @ingroup element
 */
interface ElementStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of element revision IDs for a specific element.
   *
   * @param \Drupal\element\Entity\ElementInterface $entity
   *   The element entity.
   *
   * @return int[]
   *   Element revision IDs (in ascending order).
   */
  public function revisionIds(ElementInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as element author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Element revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\element\Entity\ElementInterface $entity
   *   The element entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ElementInterface $entity);

  /**
   * Unsets the language for all element with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
