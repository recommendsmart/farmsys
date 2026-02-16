<?php

namespace Drupal\subgroup\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the RoleInheritance configuration entity.
 *
 * @ConfigEntityType(
 *   id = "subgroup_role_inheritance",
 *   label = @Translation("Role inheritance"),
 *   label_collection = @Translation("Role inheritances"),
 *   label_singular = @Translation("role inheritance"),
 *   label_plural = @Translation("role inheritances"),
 *   label_count = @PluralTranslation(
 *     singular = "@count role inheritance",
 *     plural = "@count role inheritances",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\subgroup\Entity\RoleInheritanceAccessControlHandler",
 *     "storage" = "Drupal\subgroup\Entity\RoleInheritanceStorage",
 *   },
 *   admin_permission = "administer subgroup",
 *   config_prefix = "subgroup_role_inheritance",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "source",
 *     "target",
 *     "tree",
 *   }
 * )
 */
class RoleInheritance extends ConfigEntityBase implements RoleInheritanceInterface {

  /**
   * The machine name of the group role.
   *
   * @var string
   */
  protected $id;

  /**
   * The ID of the source group role.
   *
   * @var string
   */
  protected $source;

  /**
   * The ID of the target group role.
   *
   * @var string
   */
  protected $target;

  /**
   * The ID of the tree the inheritance was set up for.
   *
   * @var string
   */
  protected $tree;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $source = $this->getSource();
    $target = $this->getTarget();
    return t('@source_role in @source_group_type -&gt; @target_role in @target_group_type', [
      '@source_role' => $source->label(),
      '@target_role' => $target->label(),
      '@source_group_type' => $source->getGroupType()->label(),
      '@target_group_type' => $target->getGroupType()->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    return $this->entityTypeManager()->getStorage('group_role')->load($this->source);
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceId() {
    return $this->source;
  }

  /**
   * {@inheritdoc}
   */
  public function getTarget() {
    return $this->entityTypeManager()->getStorage('group_role')->load($this->target);
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetId() {
    return $this->target;
  }

  /**
   * {@inheritdoc}
   */
  public function getTree() {
    return $this->tree;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    /** @var \Drupal\subgroup\Entity\SubgroupHandlerInterface $handler */
    $handler = $this->entityTypeManager()->getHandler('group_type', 'subgroup');
    $this->tree = $handler->wrapLeaf($this->getSource()->getGroupType())->getTree();
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function getListCacheTagsToInvalidate() {
    $tags = parent::getListCacheTagsToInvalidate();
    $tags[] = 'subgroup_role_inheritance_list:tree:' . $this->getTree();
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $this->addDependency('config', $this->getSource()->getConfigDependencyName());
    $this->addDependency('config', $this->getTarget()->getConfigDependencyName());
  }

}
