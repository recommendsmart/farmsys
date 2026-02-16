<?php

namespace Drupal\config_terms;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the config_terms module.
 *
 * @see config_terms.permissions.yml
 */
class ConfigTermsPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Config TermsPermissions instance.
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
   * Get config_terms permissions.
   *
   * @return array
   *   Permissions array.
   */
  public function permissions() {
    $permissions = [];
    foreach ($this->entityTypeManager->getStorage('config_terms_vocab')->loadMultiple() as $vocab) {
      $permissions += [
        'edit terms in ' . $vocab->id() => [
          'title' => $this->t('Edit terms in %vocab', ['%vocab' => $vocab->label()]),
        ],
      ];
      $permissions += [
        'delete terms in ' . $vocab->id() => [
          'title' => $this->t('Delete terms from %vocab', ['%vocab' => $vocab->label()]),
        ],
      ];
    }
    return $permissions;
  }

}
