<?php

namespace Drupal\subgroup\EventSubscriber;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\subgroup\Event\GroupLeafEvent;
use Drupal\subgroup\Event\GroupTypeLeafEvent;
use Drupal\subgroup\Event\LeafEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Clears cache tags when a leaf status changes.
 */
class TreeCacheTagInvalidator implements EventSubscriberInterface {

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * The subgroup handler for groups.
   *
   * @var \Drupal\subgroup\Entity\SubgroupHandlerInterface
   */
  protected $groupSubgroupHandler;

  /**
   * The subgroup handler for group types.
   *
   * @var \Drupal\subgroup\Entity\SubgroupHandlerInterface
   */
  protected $groupTypeSubgroupHandler;

  /**
   * Constructs a TreeCacheTagInvalidator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->groupSubgroupHandler = $entity_type_manager->getHandler('group', 'subgroup');
    $this->groupTypeSubgroupHandler = $entity_type_manager->getHandler('group_type', 'subgroup');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[LeafEvents::GROUP_LEAF_ADD] = 'onAddGroupLeaf';
    $events[LeafEvents::GROUP_LEAF_REMOVE] = 'onRemoveGroupLeaf';
    $events[LeafEvents::GROUP_TYPE_LEAF_ADD] = 'onAddGroupTypeLeaf';
    $events[LeafEvents::GROUP_TYPE_LEAF_REMOVE] = 'onRemoveGroupTypeLeaf';
    return $events;
  }

  /**
   * Invalidates the group tree cache tag when adding a leaf.
   *
   * @param \Drupal\subgroup\Event\GroupLeafEvent $event
   *   The add group leaf event.
   */
  public function onAddGroupLeaf(GroupLeafEvent $event) {
    $group = $event->getGroup();
    $this->cacheTagsInvalidator->invalidateTags($this->groupSubgroupHandler->getTreeCacheTags($group));
  }

  /**
   * Invalidates the group tree cache tag when removing a leaf.
   *
   * @param \Drupal\subgroup\Event\GroupLeafEvent $event
   *   The remove group leaf event.
   */
  public function onRemoveGroupLeaf(GroupLeafEvent $event) {
    $group = $event->getGroup();

    /** @var \Drupal\group\Entity\GroupInterface $original */
    $original = $group->original;
    $this->cacheTagsInvalidator->invalidateTags($this->groupSubgroupHandler->getTreeCacheTags($original));

  }

  /**
   * Invalidates the group type tree cache tag when adding a leaf.
   *
   * @param \Drupal\subgroup\Event\GroupTypeLeafEvent $event
   *   The add group type leaf event.
   */
  public function onAddGroupTypeLeaf(GroupTypeLeafEvent $event) {
    $group_type = $event->getGroupType();
    $this->cacheTagsInvalidator->invalidateTags($this->groupTypeSubgroupHandler->getTreeCacheTags($group_type));
  }

  /**
   * Invalidates the group type tree cache tag when removing a leaf.
   *
   * @param \Drupal\subgroup\Event\GroupTypeLeafEvent $event
   *   The remove group type leaf event.
   */
  public function onRemoveGroupTypeLeaf(GroupTypeLeafEvent $event) {
    $group_type = $event->getGroupType();

    /** @var \Drupal\group\Entity\GroupTypeInterface $original */
    $original = $group_type->original;
    $this->cacheTagsInvalidator->invalidateTags($this->groupTypeSubgroupHandler->getTreeCacheTags($original));
  }

}
