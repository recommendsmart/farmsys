<?php

namespace Drupal\flow_ui\Routing;

use Drupal\Core\Routing\EnhancerInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Enhances Flow UI routes by adding proper information about the bundle name.
 *
 * This is a copy-paste from Field UI. As we do not want to have a dependency on
 * Field UI though, this enhancer needs to be duplicated for Flow UI.
 */
class FlowUiRouteEnhancer implements EnhancerInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a FlowUiRouteEnhancer object.
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
  public function enhance(array $defaults, Request $request) {
    if (!$this->applies($defaults[RouteObjectInterface::ROUTE_OBJECT]) || !isset($defaults['entity_type_id'])) {
      return $defaults;
    }
    if (($bundle = $this->entityTypeManager->getDefinition($defaults['entity_type_id'])->getBundleEntityType()) && isset($defaults[$bundle])) {
      $defaults['bundle'] = $defaults['_raw_variables']->get($bundle);
    }

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  protected function applies(Route $route) {
    return ($route->hasOption('_flow_ui'));
  }

}
