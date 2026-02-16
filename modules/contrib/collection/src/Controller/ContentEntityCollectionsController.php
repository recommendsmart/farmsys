<?php

namespace Drupal\collection\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
// use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\collection\CollectionContentManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;


/**
 * Provides a listing of Collections for a content entity.
 */
class ContentEntityCollectionsController extends ControllerBase {

  /**
   * The collection content manager service.
   *
   * @var \Drupal\collection\CollectionContentManager
   */
  protected $collectionContentManager;

  /**
   * Constructs a ContentEntityCollectionsController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\collection\CollectionContentManager $collection_content_manager
   *   The collection content manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CollectionContentManager $collection_content_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->collectionContentManager = $collection_content_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('collection.content_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function content(Request $request) {
    $entity_type = $request->attributes->get('_route_object')->getOption('_collection_entity_type_id');
    $entity = $request->get($entity_type);
    $collection_item_definition = $this->entityTypeManager->getDefinition('collection_item');
    $entity_collection_items = $this->collectionContentManager->getCollectionItemsForEntity($entity, 'view');
    $list_builder = new \Drupal\collection\ContentEntityCollectionListBuilder($entity_collection_items, $collection_item_definition);

    $build = [
      '#theme' => 'container__content_entity_collections',
      '#children' => [
        'list' => $list_builder->render(),
      ],
      '#attributes' => [
        'class' => ['content-entity-collections']
      ],
      '#entity' => $entity,
      '#existing_collection_items' => $entity_collection_items,
    ];

    return $build;
  }

  /**
   * Provides an add title callback for ...
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return string
   *   The title for the entity add page, if the bundle was found.
   */
  public function addTitle(RouteMatchInterface $route_match) {
    $entity_type = $route_match->getRouteObject()->getOption('_collection_entity_type_id');
    $entity = $route_match->getParameter($entity_type);

    return $this->t('Collections for %title', [
      '%title' => $entity->label(),
    ]);
  }

}
