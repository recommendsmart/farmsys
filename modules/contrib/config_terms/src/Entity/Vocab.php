<?php

namespace Drupal\config_terms\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the config term vocab entity.
 *
 * @ConfigEntityType(
 *   id = "config_terms_vocab",
 *   label = @Translation("Config term vocab"),
 *   handlers = {
 *     "list_builder" = "Drupal\config_terms\VocabListBuilder",
 *     "storage" = "Drupal\config_terms\VocabStorage",
 *     "form" = {
 *       "default" = "Drupal\config_terms\Form\VocabForm",
 *       "delete" = "Drupal\config_terms\Form\VocabDeleteForm",
 *       "reset" = "Drupal\config_terms\Form\VocabResetForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\config_terms\VocabHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer config terms",
 *   config_prefix = "config_terms_vocab",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight",
 *     "uuid" = "uuid",
 *     "hierarchy" = "hierarchy"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/config-terms/add",
 *     "edit-form" = "/admin/structure/config-terms/{config_terms_vocab}/edit",
 *     "delete-form" = "/admin/structure/config-terms/{config_terms_vocab}/delete",
 *     "reset-form" = "/admin/structure/config-terms/{config_terms_vocab}/reset",
 *     "overview-form" = "/admin/structure/config-terms/{config_terms_vocab}/overview",
 *     "collection" = "/admin/structure/config-terms"
 *   },
 *   config_export = {
 *     "label",
 *     "id",
 *     "description",
 *     "weight",
 *     "hierarchy"
 *   }
 * )
 */
class Vocab extends ConfigEntityBase implements VocabInterface {

  /**
   * The config terms vocab ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Label of the vocab.
   *
   * @var string
   */
  protected $label;

  /**
   * Description of the vocab.
   *
   * @var string
   */
  protected $description;

  /**
   * The weight of this vocabulary in relation to other vocabularies.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The type of hierarchy allowed within the vocab.
   *
   * Possible values:
   * - VocabInterface::HIERARCHY_DISABLED: No parents.
   * - VocabInterface::HIERARCHY_SINGLE: Single parent.
   * - VocabInterface::HIERARCHY_MULTIPLE: Multiple parents.
   *
   * @var int
   */
  protected $hierarchy = VocabInterface::HIERARCHY_DISABLED;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getHierarchy() {
    return $this->hierarchy;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setHierarchy($hierarchy) {
    $this->hierarchy = $hierarchy;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    /**
     * @var \Drupal\config_terms\VocabStorageInterface $storage
     */
    $top_level_tids = $storage->getToplevelTids(array_keys($entities));
    $entities = $storage->loadMultiple($top_level_tids);
    // Only delete terms without a parent, child terms will get deleted too.
    $storage->delete($entities);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Reset caches.
    $storage->resetCache(array_keys($entities));

    if (reset($entities)->isSyncing()) {
      return;
    }

    // @TODO: delete fields which use only this vocab.
  }

}
