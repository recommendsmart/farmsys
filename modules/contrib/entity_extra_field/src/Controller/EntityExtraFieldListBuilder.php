<?php

namespace Drupal\entity_extra_field\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define entity extra field list builder.
 */
class EntityExtraFieldListBuilder extends EntityListBuilder {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    RouteMatchInterface $current_route_match,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct(
      $entity_type,
      $entity_type_manager->getStorage($entity_type->id())
    );
    $this->entityTypeManager = $entity_type_manager;
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(
    ContainerInterface $container,
    EntityTypeInterface $entity_type
  ) {
    return new static(
      $entity_type,
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    return [
      'label' => $this->t('Label'),
      'field_type' => $this->t('Field Type'),
      'display_type' => $this->t('Display Type'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    return [
      'label' => $entity->label(),
      'field_type' => $entity->getFieldTypeLabel(),
      'display_type' => $entity->getDisplayType(),
    ] + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds(): array {
    $query = $this->getStorage()->getQuery();

    if ($base_entity_type_id = $this->getBaseEntityTypeId()) {
      $query->condition('base_entity_type_id', $base_entity_type_id);
    }
    if ($base_entity_bundle_type = $this->getBaseEntityBundleType()) {
      $query->condition('base_bundle_type_id', $base_entity_bundle_type->id());
    }
    $query->sort($this->entityType->getKey('id'));

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

  /**
   * Get base entity type identifier.
   *
   * @return string|null
   *   The base entity type identifier.
   */
  protected function getBaseEntityTypeId(): ?string {
    return $this->currentRouteMatch->getParameter('entity_type_id');
  }

  /**
   * Get base entity bundle type.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface|bool
   *   The configuration entity; otherwise NULL if it doesn't exist.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getBaseEntityBundleType() {
    $entity_type_id = $this->getBaseEntityTypeId();

    $entity_bundle_type_id = $this->entityTypeManager
      ->getDefinition($entity_type_id)
      ->getBundleEntityType();

    if (!isset($entity_bundle_type_id)) {
      return NULL;
    }

    return $this->currentRouteMatch->getParameter($entity_bundle_type_id);
  }

}
