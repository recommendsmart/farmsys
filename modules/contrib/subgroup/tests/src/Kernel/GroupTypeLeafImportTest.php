<?php

namespace Drupal\Tests\subgroup\Kernel;

use Drupal\Core\Site\Settings;

/**
 * Tests the import or synchronization of group type leaves.
 *
 * @group subgroup
 */
class GroupTypeLeafImportTest extends SubgroupKernelTestBase {

  /**
   * The content enabler plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // The system.site key is required for import validation.
    // See: https://www.drupal.org/project/drupal/issues/2995062
    $this->installConfig(['system']);
    $this->pluginManager = $this->container->get('plugin.manager.group_content_enabler');
  }

  /**
   * Tests special behavior during group type import.
   *
   * @covers \Drupal\subgroup\EventSubscriber\GroupTypeLeafSubscriber::onImportLeaf
   */
  public function testImport() {
    // Simulate config data to import.
    $active = $this->container->get('config.storage');
    $sync = $this->container->get('config.storage.sync');
    $this->copyConfig($active, $sync);

    // Manually add the 'import' group type to the synchronization directory.
    $test_dir = __DIR__ . '/../../modules/subgroup_test/sync';
    $sync_dir = Settings::get('config_sync_directory');
    $file_system = $this->container->get('file_system');
    $file_system->copy("$test_dir/group.type.parent.yml", "$sync_dir/group.type.parent.yml");
    $file_system->copy("$test_dir/group.type.child.yml", "$sync_dir/group.type.child.yml");
    $file_system->copy("$test_dir/group.content_type.parent-subgroup-child.yml", "$sync_dir/group.content_type.parent-subgroup-child.yml");

    // Import the content of the sync directory.
    $this->configImporter()->import();

    // Check that the group types were created.
    $group_type_storage = $this->entityTypeManager->getStorage('group_type');
    $this->assertNotNull($group_type_storage->load('parent'), 'Parent group type was loaded successfully.');
    $this->assertNotNull($group_type_storage->load('child'), 'Child group type was loaded successfully.');

    // Check that the group content type was created.
    $group_content_type_storage = $this->entityTypeManager->getStorage('group_content_type');
    $this->assertNotNull($group_content_type_storage->load('parent-subgroup-child'), 'Group content type was loaded successfully.');

    // Check that subgroup plugin definitions were updated.
    $plugin_definition = $this->pluginManager->getDefinition('subgroup:child');
    $this->assertNotNull($plugin_definition, 'Plugin definitions were updated during import.');
  }

}
