<?php

namespace Drupal\subgroup\Plugin\GroupContentEnabler;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\subgroup\Entity\SubgroupHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for subgroup plugins.
 */
class SubgroupDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The group type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $storage;

  /**
   * The group type subgroup handler.
   *
   * @var \Drupal\subgroup\Entity\SubgroupHandlerInterface
   */
  protected $subgroupHandler;

  /**
   * Constructs a new SubgroupDeriver.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   *   The group type storage.
   * @param \Drupal\subgroup\Entity\SubgroupHandlerInterface $subgroup_handler
   *   The group type subgroup handler.
   */
  public function __construct(ConfigEntityStorageInterface $storage, SubgroupHandlerInterface $subgroup_handler) {
    $this->storage = $storage;
    $this->subgroupHandler = $subgroup_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type_manager->getStorage('group_type'),
      $entity_type_manager->getHandler('group_type', 'subgroup')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    foreach ($this->storage->loadMultiple() as $group_type_id => $group_type) {
      if (!$this->subgroupHandler->isLeaf($group_type) || $this->subgroupHandler->isRoot($group_type)) {
        continue;
      }

      $label = $group_type->label();
      $this->derivatives[$group_type_id] = [
        'entity_bundle' => $group_type_id,
        'label' => t('Subgroup (@group_type)', ['@group_type' => $label]),
        'description' => t('Adds %group_type groups as subgroups.', ['%group_type' => $label]),
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
