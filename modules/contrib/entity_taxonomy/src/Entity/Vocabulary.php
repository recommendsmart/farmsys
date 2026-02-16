<?php

namespace Drupal\entity_taxonomy\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\entity_taxonomy\VocabularyInterface;

/**
 * Defines the entity_taxonomy vocabulary entity.
 *
 * @ConfigEntityType(
 *   id = "entity_taxonomy_vocabulary",
 *   label = @Translation("entity_taxonomy vocabulary"),
 *   label_singular = @Translation("vocabulary"),
 *   label_plural = @Translation("vocabularies"),
 *   label_collection = @Translation("entity_taxonomy"),
 *   label_count = @PluralTranslation(
 *     singular = "@count vocabulary",
 *     plural = "@count vocabularies"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\entity_taxonomy\VocabularyStorage",
 *     "list_builder" = "Drupal\entity_taxonomy\VocabularyListBuilder",
 *     "access" = "Drupal\entity_taxonomy\VocabularyAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\entity_taxonomy\VocabularyForm",
 *       "reset" = "Drupal\entity_taxonomy\Form\VocabularyResetForm",
 *       "delete" = "Drupal\entity_taxonomy\Form\VocabularyDeleteForm",
 *       "overview" = "Drupal\entity_taxonomy\Form\OverviewTerms"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\entity_taxonomy\Entity\Routing\VocabularyRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer entity_taxonomy",
 *   config_prefix = "vocabulary",
 *   bundle_of = "entity_taxonomy_term",
 *   entity_keys = {
 *     "id" = "vid",
 *     "label" = "name",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/entity_taxonomy/add",
 *     "delete-form" = "/admin/structure/entity_taxonomy/manage/{entity_taxonomy_vocabulary}/delete",
 *     "reset-form" = "/admin/structure/entity_taxonomy/manage/{entity_taxonomy_vocabulary}/reset",
 *     "overview-form" = "/admin/structure/entity_taxonomy/manage/{entity_taxonomy_vocabulary}/overview",
 *     "edit-form" = "/admin/structure/entity_taxonomy/manage/{entity_taxonomy_vocabulary}",
 *     "collection" = "/admin/structure/entity_taxonomy",
 *   },
 *   config_export = {
 *     "name",
 *     "vid",
 *     "description",
 *     "weight",
 *   }
 * )
 */
class Vocabulary extends ConfigEntityBundleBase implements VocabularyInterface {

  /**
   * The entity_taxonomy vocabulary ID.
   *
   * @var string
   */
  protected $vid;

  /**
   * Name of the vocabulary.
   *
   * @var string
   */
  protected $name;

  /**
   * Description of the vocabulary.
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
   * {@inheritdoc}
   */
  public function id() {
    return $this->vid;
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
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    // Only load terms without a parent, child terms will get deleted too.
    $term_storage = \Drupal::entityTypeManager()->getStorage('entity_taxonomy_term');
    $terms = $term_storage->loadMultiple($storage->getToplevelTids(array_keys($entities)));
    $term_storage->delete($terms);
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

    $vocabularies = [];
    foreach ($entities as $vocabulary) {
      $vocabularies[$vocabulary->id()] = $vocabulary->id();
    }
    // Load all entity_taxonomy module fields and delete those which use only this
    // vocabulary.
    $field_storages = \Drupal::entityTypeManager()->getStorage('field_storage_config')->loadByProperties(['module' => 'entity_taxonomy']);
    foreach ($field_storages as $field_storage) {
      $modified_storage = FALSE;
      // Term reference fields may reference terms from more than one
      // vocabulary.
      foreach ($field_storage->getSetting('allowed_values') as $key => $allowed_value) {
        if (isset($vocabularies[$allowed_value['vocabulary']])) {
          $allowed_values = $field_storage->getSetting('allowed_values');
          unset($allowed_values[$key]);
          $field_storage->setSetting('allowed_values', $allowed_values);
          $modified_storage = TRUE;
        }
      }
      if ($modified_storage) {
        $allowed_values = $field_storage->getSetting('allowed_values');
        if (empty($allowed_values)) {
          $field_storage->delete();
        }
        else {
          // Update the field definition with the new allowed values.
          $field_storage->save();
        }
      }
    }
  }

}
