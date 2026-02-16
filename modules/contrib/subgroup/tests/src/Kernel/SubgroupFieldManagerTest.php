<?php

namespace Drupal\Tests\subgroup\Kernel;

/**
 * Tests the Subgroup field manager.
 *
 * @coversDefaultClass \Drupal\subgroup\SubgroupFieldManager
 * @group subgroup
 */
class SubgroupFieldManagerTest extends SubgroupKernelTestBase {

  /**
   * The subgroup field manager to test.
   *
   * @var \Drupal\subgroup\SubgroupFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * The group type to run tests on.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupType;

  /**
   * The field names the manager takes care of.
   *
   * @var string[]
   */
  protected $fieldNames;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->fieldManager = $this->container->get('subgroup.field_manager');
    $this->groupType = $this->createGroupType();
    $this->fieldNames = [
      SUBGROUP_DEPTH_FIELD,
      SUBGROUP_LEFT_FIELD,
      SUBGROUP_RIGHT_FIELD,
      SUBGROUP_TREE_FIELD,
    ];
  }

  /**
   * Tests the installation of fields.
   *
   * @covers ::installFields
   */
  public function testInstallFields() {
    $group_type_id = $this->groupType->id();
    $fc_storage = $this->entityTypeManager->getStorage('field_config');

    foreach ($this->fieldNames as $field_name) {
      $field = $fc_storage->load("group.$group_type_id.$field_name");
      $this->assertNull($field, "Field $field_name does not exist on the group type yet.");
    }

    $this->fieldManager->installFields($group_type_id);
    foreach ($this->fieldNames as $field_name) {
      $field = $fc_storage->load("group.$group_type_id.$field_name");
      $this->assertNotNull($field, "Field $field_name was created on the group type.");
    }
  }

  /**
   * Tests the exception if fields already exist.
   *
   * @covers ::installFields
   * @depends testInstallFields
   */
  public function testInstallFieldsException() {
    $group_type_id = $this->groupType->id();
    $fsc_storage = $this->entityTypeManager->getStorage('field_storage_config');
    $fc_storage = $this->entityTypeManager->getStorage('field_config');

    $field_storage = $fsc_storage->load('group.' . SUBGROUP_DEPTH_FIELD);
    $fc_storage->create([
      'field_storage' => $field_storage,
      'bundle' => $group_type_id,
      'label' => 'Foo',
    ])->save();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage(sprintf('The field "%s" already exists on group type "%s".', SUBGROUP_DEPTH_FIELD, $group_type_id));
    $this->fieldManager->installFields($group_type_id);
  }

  /**
   * Tests the deletion of fields.
   *
   * @covers ::deleteFields
   * @depends testInstallFields
   */
  public function testDeleteFields() {
    $group_type_id = $this->groupType->id();
    $fc_storage = $this->entityTypeManager->getStorage('field_config');
    $this->fieldManager->installFields($group_type_id);
    $this->fieldManager->deleteFields($group_type_id);
    foreach ($this->fieldNames as $field_name) {
      $field = $fc_storage->load("group.$group_type_id.$field_name");
      $this->assertNull($field, "Field $field_name no longer not exist on the group type.");
    }
  }

  /**
   * Tests the exception if fields do not exist.
   *
   * @covers ::deleteFields
   * @depends testDeleteFields
   */
  public function testDeleteFieldsException() {
    $group_type_id = $this->groupType->id();
    $fc_storage = $this->entityTypeManager->getStorage('field_config');
    $this->fieldManager->installFields($group_type_id);
    $fc_storage->load("group.$group_type_id." . SUBGROUP_DEPTH_FIELD)->delete();
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage(sprintf('The field "%s" does not exist on group type "%s".', SUBGROUP_DEPTH_FIELD, $group_type_id));
    $this->fieldManager->deleteFields($group_type_id);
  }

}
