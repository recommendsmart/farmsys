<?php

namespace Drupal\social_course\Access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\node\Access\NodeAddAccessCheck;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Determines access to for node add pages.
 *
 * @ingroup node_access
 */
class ContentAccessCheck extends NodeAddAccessCheck {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($entity_type_manager);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, NodeTypeInterface $node_type = NULL) {
    $forbidden = ['course_article', 'course_section', 'course_video'];

    // Allow other modules to register their own types.
    $this->moduleHandler->alter('social_course_material_types', $forbidden);

    if ($node_type !== NULL && in_array($node_type->id(), $forbidden)) {
      return AccessResult::forbidden();
    }

    return parent::access($account, $node_type);
  }

}
