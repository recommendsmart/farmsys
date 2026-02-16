<?php

namespace Drupal\element;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
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
class ElementStorage extends SqlContentEntityStorage implements ElementStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(ElementInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {element_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {element_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(ElementInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {element_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('element_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
