<?php

namespace Drupal\flow_ui\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\flow\FlowTaskMode;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Flow UI routes.
 */
class FlowUiRouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a RouteSubscriber object.
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
  protected function alterRoutes(RouteCollection $collection) {
    $route = new Route(
      "/admin/structure/flow",
      [
        '_title' => 'Flow (automation)',
        '_entity_list' => 'flow',
      ],
      ['_permission' => 'administer flow'],
      ['_flow_ui' => TRUE]
    );
    $collection->add("entity.flow.collection", $route);
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if (!$route_name = $entity_type->get('field_ui_base_route')) {
        continue;
      }
      if (!$entity_route = $collection->get($route_name)) {
        continue;
      }
      $path = $entity_route->getPath();

      $options = $entity_route->getOptions();
      if ($bundle_entity_type = $entity_type->getBundleEntityType()) {
        $options['parameters'][$bundle_entity_type] = [
          'type' => 'entity:' . $bundle_entity_type,
        ];
      }
      // Special parameter used to easily recognize all Flow UI routes.
      $options['_flow_ui'] = TRUE;

      $defaults = [
        '_controller' => '\Drupal\flow_ui\Controller\FlowUiController::flowForm',
        'entity_type_id' => $entity_type_id,
      ];
      // If the entity type has no bundles and it doesn't use {bundle} in its
      // admin path, use the entity type.
      if (strpos($path, '{bundle}') === FALSE) {
        $defaults['bundle'] = !$entity_type->hasKey('bundle') ? $entity_type_id : '';
      }

      // Use permissions as requirement when no task mode is provided.
      $permissions = ['_permission' => 'administer flow+administer ' . $entity_type_id . ' flow'];
      // Use custom access as requirement when a task mode is provided.
      $custom_acccess = ['_custom_access' => '\Drupal\flow_ui\Controller\FlowUiController::customFlowAccess'];

      $route = new Route(
        "$path/flow",
        [
          '_title' => 'Manage flow',
          'flow_task_mode' => FlowTaskMode::service()->getDefaultTaskMode(),
        ] + $defaults,
        $custom_acccess,
        $options
      );
      $collection->add("entity.flow.{$entity_type_id}.default", $route);
      $route = new Route(
        "$path/flow/{flow_task_mode}",
        [
          '_title' => 'Manage flow',
        ] + $defaults,
        $custom_acccess,
        $options
      );
      $collection->add("entity.flow.{$entity_type_id}.task_mode", $route);
      $route = new Route(
        "$path/flow/{flow_task_mode}/delete",
        [
          '_title' => 'Delete flow',
          '_controller' => '\Drupal\flow_ui\Controller\FlowUiController::flowDeleteForm',
        ] + $defaults,
        $custom_acccess,
        $options
      );
      $collection->add("entity.flow.{$entity_type_id}.delete", $route);
      $route = new Route(
        "$path/flow/custom/add",
        [
          '_title' => 'Add custom flow',
          '_controller' => '\Drupal\flow_ui\Controller\FlowUiController::customAddForm',
        ] + $defaults,
        $permissions,
        $options
      );
      $collection->add("entity.flow.{$entity_type_id}.custom.add", $route);

      $route = new Route(
        "$path/flow/{flow_task_mode}/task/add/{flow_task_plugin}/{flow_subject_plugin}",
        [
          '_title' => 'Add task',
          '_controller' => '\Drupal\flow_ui\Controller\FlowUiController::taskAddForm',
        ] + $defaults,
        $custom_acccess,
        $options
      );
      $collection->add("flow.task.{$entity_type_id}.add", $route);
      $route = new Route(
        "$path/flow/{flow_task_mode}/task/edit/{flow_task_index}",
        [
          '_title' => 'Edit task',
          '_controller' => '\Drupal\flow_ui\Controller\FlowUiController::taskEditForm',
        ] + $defaults,
        $custom_acccess,
        $options
      );
      $collection->add("flow.task.{$entity_type_id}.edit", $route);
      $route = new Route(
        "$path/flow/{flow_task_mode}/task/enable/{flow_task_index}",
        [
          '_title' => 'Enable task',
          '_controller' => '\Drupal\flow_ui\Controller\FlowUiController::taskEnableForm',
        ] + $defaults,
        $custom_acccess,
        $options
      );
      $collection->add("flow.task.{$entity_type_id}.enable", $route);
      $route = new Route(
        "$path/flow/{flow_task_mode}/task/disable/{flow_task_index}",
        [
          '_title' => 'Disable task',
          '_controller' => '\Drupal\flow_ui\Controller\FlowUiController::taskDisableForm',
        ] + $defaults,
        $custom_acccess,
        $options
      );
      $collection->add("flow.task.{$entity_type_id}.disable", $route);
      $route = new Route(
        "$path/flow/{flow_task_mode}/task/delete/{flow_task_index}",
        [
          '_title' => 'Delete task',
          '_controller' => '\Drupal\flow_ui\Controller\FlowUiController::taskDeleteForm',
        ] + $defaults,
        $custom_acccess,
        $options
      );
      $collection->add("flow.task.{$entity_type_id}.delete", $route);

      $route = new Route(
        "$path/flow/{flow_task_mode}/qualifier/add/{flow_qualifier_plugin}/{flow_subject_plugin}",
        [
          '_title' => 'Add qualifier',
          '_controller' => '\Drupal\flow_ui\Controller\FlowUiController::qualifierAddForm',
        ] + $defaults,
        $custom_acccess,
        $options
      );
      $collection->add("flow.qualifier.{$entity_type_id}.add", $route);
      $route = new Route(
        "$path/flow/{flow_task_mode}/qualifier/edit/{flow_qualifier_index}",
        [
          '_title' => 'Edit qualifier',
          '_controller' => '\Drupal\flow_ui\Controller\FlowUiController::qualifierEditForm',
        ] + $defaults,
        $custom_acccess,
        $options
      );
      $collection->add("flow.qualifier.{$entity_type_id}.edit", $route);
      $route = new Route(
        "$path/flow/{flow_task_mode}/qualifier/delete/{flow_qualifier_index}",
        [
          '_title' => 'Delete qualifier',
          '_controller' => '\Drupal\flow_ui\Controller\FlowUiController::qualifierDeleteForm',
        ] + $defaults,
        $custom_acccess,
        $options
      );
      $collection->add("flow.qualifier.{$entity_type_id}.delete", $route);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -75];
    return $events;
  }

}
