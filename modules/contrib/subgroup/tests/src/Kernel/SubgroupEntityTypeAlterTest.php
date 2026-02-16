<?php

namespace Drupal\Tests\subgroup\Kernel;

use Drupal\subgroup\Entity\GroupSubgroupHandler;
use Drupal\subgroup\Entity\GroupTypeSubgroupHandler;
use Drupal\subgroup\GroupLeaf;
use Drupal\subgroup\GroupTypeLeaf;

/**
 * Tests the alterations made to the Group and GroupType entity types.
 *
 * @group subgroup
 */
class SubgroupEntityTypeAlterTest extends SubgroupKernelTestBase {

  /**
   * Tests the entity type alterations.
   *
   * @uses subgroup_entity_type_alter
   */
  public function testEntityTypeAlterations() {
    $group = $this->entityTypeManager->getDefinition('group');
    $this->assertEquals(GroupLeaf::class, $group->get('subgroup_wrapper'), 'The "subgroup_wrapper" class was set for the Group entity type.');
    $this->assertEquals(GroupSubgroupHandler::class, $group->getHandlerClass('subgroup'), 'The "subgroup" handler was set for the Group entity type.');

    $group_type = $this->entityTypeManager->getDefinition('group_type');
    $this->assertEquals(GroupTypeLeaf::class, $group_type->get('subgroup_wrapper'), 'The "subgroup_wrapper" class was set for the GroupType entity type.');
    $this->assertEquals(GroupTypeSubgroupHandler::class, $group_type->getHandlerClass('subgroup'), 'The "subgroup" handler was set for the GroupType entity type.');
  }

}
