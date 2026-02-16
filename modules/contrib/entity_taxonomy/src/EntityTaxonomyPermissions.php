<?php

namespace Drupal\entity_taxonomy;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the entity_taxonomy module.
 *
 * @see entity_taxonomy.permissions.yml
 */
class EntityTaxonomyPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a entity_taxonomyPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Get entity_taxonomy permissions.
   *
   * @return array
   *   Permissions array.
   */
  public function permissions() {
    $permissions = [];
    foreach (Vocabulary::loadMultiple() as $vocabulary) {
      $permissions += $this->buildPermissions($vocabulary);
    }
    return $permissions;
  }

  /**
   * Builds a standard list of entity_taxonomy term permissions for a given vocabulary.
   *
   * @param \Drupal\entity_taxonomy\VocabularyInterface $vocabulary
   *   The vocabulary.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  protected function buildPermissions(VocabularyInterface $vocabulary) {
    $id = $vocabulary->id();
    $args = ['%vocabulary' => $vocabulary->label()];

    return [
      "create terms in $id" => ['title' => $this->t('%vocabulary: Create terms', $args)],
      "delete terms in $id" => ['title' => $this->t('%vocabulary: Delete terms', $args)],
      "edit terms in $id" => ['title' => $this->t('%vocabulary: Edit terms', $args)],
    ];
  }

}
