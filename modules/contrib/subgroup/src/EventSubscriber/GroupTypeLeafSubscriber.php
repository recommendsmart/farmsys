<?php

namespace Drupal\subgroup\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\subgroup\Event\GroupTypeLeafEvent;
use Drupal\subgroup\Event\LeafEvents;
use Drupal\subgroup\SubgroupFieldManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts to group type leaf status changes.
 */
class GroupTypeLeafSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The subgroup handler for group types.
   *
   * @var \Drupal\subgroup\Entity\SubgroupHandlerInterface
   */
  protected $subgroupHandler;

  /**
   * The group content enabler plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * The subgroup field manager.
   *
   * @var \Drupal\subgroup\SubgroupFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * Constructs a new GroupTypeLeafSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager
   *   The group content enabler plugin manager.
   * @param \Drupal\subgroup\SubgroupFieldManagerInterface $field_manager
   *   The subgroup field manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GroupContentEnablerManagerInterface $plugin_manager, SubgroupFieldManagerInterface $field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->subgroupHandler = $entity_type_manager->getHandler('group_type', 'subgroup');
    $this->pluginManager = $plugin_manager;
    $this->fieldManager = $field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[LeafEvents::GROUP_TYPE_LEAF_ADD] = 'onAddLeaf';
    $events[LeafEvents::GROUP_TYPE_LEAF_IMPORT] = 'onImportLeaf';
    $events[LeafEvents::GROUP_TYPE_LEAF_REMOVE] = 'onRemoveLeaf';
    return $events;
  }

  /**
   * Handles the add leaf event.
   *
   * @param \Drupal\subgroup\Event\GroupTypeLeafEvent $event
   *   The add leaf event.
   */
  public function onAddLeaf(GroupTypeLeafEvent $event) {
    $group_type = $event->getGroupType();

    $this->fieldManager->installFields($group_type->id());
    $this->pluginManager->clearCachedDefinitions();

    if (!$this->subgroupHandler->isRoot($group_type)) {
      /** @var \Drupal\group\Entity\GroupTypeInterface $parent */
      $parent = $this->subgroupHandler->getParent($group_type);

      /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage('group_content_type');
      $storage->save($storage->createFromPlugin($parent, 'subgroup:' . $group_type->id()));
    }
  }

  /**
   * Handles the import leaf event.
   *
   * @param \Drupal\subgroup\Event\GroupTypeLeafEvent $event
   *   The import leaf event.
   */
  public function onImportLeaf(GroupTypeLeafEvent $event) {
    $this->pluginManager->clearCachedDefinitions();
  }

  /**
   * Handles the remove leaf event.
   *
   * @param \Drupal\subgroup\Event\GroupTypeLeafEvent $event
   *   The remove leaf event.
   */
  public function onRemoveLeaf(GroupTypeLeafEvent $event) {
    $group_type = $event->getGroupType();

    /** @var \Drupal\group\Entity\GroupTypeInterface $original */
    $original = $group_type->original;

    /** @var \Drupal\subgroup\Entity\RoleInheritanceStorageInterface $role_inheritance_storage */
    $role_inheritance_storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
    $role_inheritance_storage->deleteForGroupType($group_type, $this->subgroupHandler->wrapLeaf($original)->getTree());

    /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    $storage->delete($storage->loadByContentPluginId('subgroup:' . $group_type->id()));

    $this->fieldManager->deleteFields($group_type->id());
    $this->pluginManager->clearCachedDefinitions();
  }

}
