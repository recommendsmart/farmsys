<?php

namespace Drupal\collection\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\ContentEntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines dynamic routes for Collections and related.
 */
class CollectionDynamicRoutes implements ContainerInjectionInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CollectionDynamicRoutes object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $route_collection = new RouteCollection();

    /* @var $definition EntityTypeInterface */
    foreach ($this->entityTypeManager->getDefinitions() as $definition) {
      $is_content_entity = $definition instanceof ContentEntityType;
      $has_canonical_link_template = $definition->hasLinkTemplate('canonical');
      $blocked_entity_types = ['collection_item'];

      if (!$is_content_entity || !$has_canonical_link_template || in_array($definition->id(), $blocked_entity_types)) {
        continue;
      }

      // Some content entities, like menu_link_content and media (if
      // media.settings.standalone_url is enabled), use their /edit path as
      // canonical. Remove that here.
      $path_name = preg_replace('|/edit$|', '', $definition->getLinkTemplate('canonical')) . '/collections';

      $route = new Route(
        $path_name,
        // Route defaults:
        [
          '_controller' => '\Drupal\collection\Controller\ContentEntityCollectionsController::content',
          '_title_callback' => '\Drupal\collection\Controller\ContentEntityCollectionsController::addTitle'
        ],
        // Route requirements:
        [
          '_permission' => 'edit own collections',
        ],
        // Route options:
        [
          '_admin_route' => 'TRUE',
          '_collection_entity_type_id' => $definition->id(),
          'parameters' => [
            $definition->id() => [
              'type' => 'entity:' . $definition->id(),
            ],
          ],
        ]
      );

      $route_collection->add('collection.' . $definition->id() . '.collections', $route);
    }

    return $route_collection;
  }

}
