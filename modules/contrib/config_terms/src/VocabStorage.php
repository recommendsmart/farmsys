<?php

namespace Drupal\config_terms;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\config_terms\Entity\VocabInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a storage handler class for taxonomy vocabularies.
 */
class VocabStorage extends ConfigEntityStorage implements VocabStorageInterface {

  /**
   * The term storage handler.
   *
   * @var \Drupal\config_terms\TermStorageInterface
   */
  protected $termStorage;

  /**
   * Constructs a ConfigEntityStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   The memory cache backend.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, MemoryCacheInterface $memory_cache, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager, $memory_cache);
    $this->termStorage = $entity_type_manager->getStorage('config_terms_term');
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager'),
      $container->get('entity.memory_cache'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getToplevelTids(array $vids) {
    $query = $this->termStorage->getQuery()->condition('vid', $vids, 'IN');
    $tids = $query->execute();
    $toplevel_tids = [];

    /**
     * @var \Drupal\config_terms\Entity\TermInterface $term
     */
    foreach ($this->termStorage->loadMultiple($tids) as $term) {
      if ($term->getParents() === ['0']) {
        $toplevel_tids[] = $term->id();
      }
    }
    return $toplevel_tids;
  }

  /**
   * {@inheritdoc}
   */
  public function getVocabsList() {
    // @see https://www.drupal.org/node/2862699
    // Multiple sorts don't work for config entities, so implement it here.
    $query = $this->getQuery();
    $vid_list = $query->execute();
    /** @var \Drupal\config_terms\Entity\VocabInterface[] $vocabs */
    $vocabs = $this->loadMultiple($vid_list);
    usort($vocabs, [$this, 'weightLabelCmp']);
    $list = [];
    foreach ($vocabs as $vocab) {
      $list[$vocab->id()] = $vocab->getLabel();
    }
    return $list;
  }

  /**
   * Comparison function for weight and label.
   *
   * @param \Drupal\config_terms\Entity\VocabInterface $a
   *   First vocabulary.
   * @param \Drupal\config_terms\Entity\VocabInterface $b
   *   Second vocabulary.
   *
   * @return int
   *   0 if the two vocabs sort the same, -1 if $a is less than $b, 1 otherwise.
   */
  protected function weightLabelCmp(VocabInterface $a, VocabInterface $b) {
    if ($a->getWeight() == $b->getWeight()) {
      if ($a->getLabel() == $b->getLabel()) {
        return 0;
      }
      return $a->getLabel() < $b->getLabel() ? -1 : 1;
    }
    return $a->getWeight() < $b->getWeight() ? -1 : 1;
  }

}
