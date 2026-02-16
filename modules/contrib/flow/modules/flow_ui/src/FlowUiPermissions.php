<?php

namespace Drupal\flow_ui;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the Flow UI module.
 */
class FlowUiPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new FieldUiPermissions instance.
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
   * Returns an array of Flow UI permissions.
   *
   * @return array
   *   The permissions.
   */
  public function flowPermissions(): array {
    $permissions = [
      'administer flow' => [
        'title' => $this->t('Administer any flow'),
        'description' => $this->t('Create, edit and delete all existing Flow configurations.'),
        'restrict access' => TRUE,
      ],
    ];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if (!$entity_type->get('field_ui_base_route')) {
        continue;
      }
      // Create a permission for each fieldable entity.
      $permissions['administer ' . $entity_type_id . ' flow'] = [
        'title' => $this->t('%entity_label: Administer flow', ['%entity_label' => $entity_type->getLabel()]),
        'restrict access' => TRUE,
        'dependencies' => ['module' => [$entity_type->getProvider()]],
      ];
    }

    return $permissions;
  }

}
