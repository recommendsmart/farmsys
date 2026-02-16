<?php

declare(strict_types=1);

namespace Drupal\entity_extra_field_ui\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Define the route subscriber.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Route subscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Alters existing routes for a specific collection.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection for adding routes.
   */
  protected function alterRoutes(RouteCollection $collection): void {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $definition) {
      $base_route_name = $definition->get('field_ui_base_route');

      if (!isset($base_route_name)) {
        continue;
      }
      $entity_route = $collection->get($base_route_name);

      if (!isset($entity_route)) {
        continue;
      }
      $entity_path = $entity_route->getPath();
      $entity_options = $entity_route->getOptions();
      $entity_bundle_type = $definition->getBundleEntityType();

      if (isset($entity_bundle_type)) {
        $entity_options['parameters'][$entity_bundle_type] = [
          'type' => 'entity:' . $entity_bundle_type,
        ];
      }

      $route = new Route("$entity_path/extra-fields");
      $route->setDefaults([
        '_title' => 'Manage extra fields',
        '_entity_list' => 'entity_extra_field',
        'entity_type_id' => $entity_type_id,
      ])->setRequirements([
        '_permission' => 'administer entity extra field',
      ])->setOptions($entity_options);

      $collection->add("entity.{$entity_type_id}.extra_fields", $route);

      $route = new Route("$entity_path/extra-fields/add");
      $route->setDefaults([
        '_title' => 'Add extra field',
        '_entity_form' => 'entity_extra_field.add',
        'entity_type_id' => $entity_type_id,
      ])->setRequirements([
        '_permission' => 'administer entity extra field',
      ])->setOptions($entity_options);

      $collection->add("entity.{$entity_type_id}.extra_fields.add", $route);

      $route = new Route("$entity_path/extra-fields/{entity_extra_field}/edit");
      $route->setDefaults([
        '_title' => 'Edit extra field',
        '_entity_form' => 'entity_extra_field.edit',
        'entity_type_id' => $entity_type_id,
      ])->setRequirements([
        '_permission' => 'administer entity extra field',
      ])->setOptions($entity_options);

      $collection->add("entity.{$entity_type_id}.extra_fields.edit", $route);

      $route = new Route("$entity_path/extra-fields/{entity_extra_field}/delete");
      $route->setDefaults([
        '_title' => 'Delete extra field',
        '_entity_form' => 'entity_extra_field.delete',
        'entity_type_id' => $entity_type_id,
      ])->setRequirements([
        '_permission' => 'administer entity extra field',
      ])->setOptions($entity_options);

      $collection->add("entity.{$entity_type_id}.extra_fields.delete", $route);
    }
  }

}
