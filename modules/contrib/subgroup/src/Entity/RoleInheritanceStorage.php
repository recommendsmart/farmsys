<?php

namespace Drupal\subgroup\Entity;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\subgroup\InvalidInheritanceException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the storage handler class for role inheritance entities.
 */
class RoleInheritanceStorage extends ConfigEntityStorage implements RoleInheritanceStorageInterface {

  /**
   * The subgroup handler for group types.
   *
   * @var \Drupal\subgroup\Entity\SubgroupHandlerInterface
   */
  protected $subgroupHandler;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    /** @var static $instance */
    $instance = parent::createInstance($container, $entity_type);
    $instance->subgroupHandler = $container->get('entity_type.manager')->getHandler('group_type', 'subgroup');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    if (!$entity->isNew()) {
      throw new EntityStorageException('Role inheritance entities may not be updated after creation.');
    }

    /** @var \Drupal\subgroup\Entity\RoleInheritanceInterface $entity */
    if (empty($entity->getSourceId())) {
      throw new EntityMalformedException('Source property is required for a RoleInheritance entity.');
    }
    if (empty($entity->getTargetId())) {
      throw new EntityMalformedException('Target property is required for a RoleInheritance entity.');
    }

    $source_role = $entity->getSource();
    $target_role = $entity->getTarget();
    if (!$source_role->isMember()) {
      throw new InvalidInheritanceException('Source role must be assignable to members.');
    }
    if (!$target_role->isMember()) {
      throw new InvalidInheritanceException('Target role must be assignable to members.');
    }

    $source_group_type = $source_role->getGroupType();
    $target_group_type = $target_role->getGroupType();
    if (!$this->subgroupHandler->isLeaf($source_group_type)) {
      throw new InvalidInheritanceException('Source role must belong to a group type that is part of a tree.');
    }
    if (!$this->subgroupHandler->isLeaf($target_group_type)) {
      throw new InvalidInheritanceException('Target role must belong to a group type that is part of a tree.');
    }

    $source_leaf = $this->subgroupHandler->wrapLeaf($source_group_type);
    $target_leaf = $this->subgroupHandler->wrapLeaf($target_group_type);
    if ($source_leaf->getTree() !== $target_leaf->getTree()) {
      throw new InvalidInheritanceException('Source role and target role must belong to group types from the same tree.');
    }

    if ($this->loadByProperties(['source' => $source_role->id(), 'target' => $target_role->id()])) {
      throw new InvalidInheritanceException('The provided combination of source and destination role exists already.');
    }

    if (!$this->subgroupHandler->areVerticallyRelated($source_group_type, $target_group_type)) {
      throw new InvalidInheritanceException('Source role and target role must belong to group types that are vertically related (e.g.: parent-grandson, not siblings).');
    }

    return parent::save($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteForGroupType(GroupTypeInterface $group_type, $original_tree) {
    $to_delete = [];
    $group_type_id = $group_type->id();
    foreach ($this->loadByProperties(['tree' => $original_tree]) as $role_inheritance) {
      /** @var \Drupal\subgroup\Entity\RoleInheritanceInterface $role_inheritance */
      $source_group_type_id = $role_inheritance->getSource()->getGroupTypeId();
      $target_group_type_id = $role_inheritance->getTarget()->getGroupTypeId();

      if ($source_group_type_id === $group_type_id || $target_group_type_id === $group_type_id) {
        $to_delete[] = $role_inheritance;
      }
    }

    if (!empty($to_delete)) {
      $this->delete($to_delete);
    }
  }

}
