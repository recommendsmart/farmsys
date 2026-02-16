<?php

namespace Drupal\Tests\group_members_extra\Kernel;

use Drupal\group\Entity\Group;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\user\Entity\User;
use Drupal\views\Views;

/**
 * Tests the group members and group contacts overviews.
 *
 * @group group_members_extra
 */
class GroupMembersExtraOverview extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group', 'options', 'entity', 'variationcache', 'field', 'text', 'group_members_extra', 'user', 'views'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installEntitySchema('user');
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_type');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('group_content_type');
    $this->installConfig(['group', 'field', 'group_members_extra']);

    // Set the current user so group creation can rely on it.
    $account = User::create(['name' => $this->randomString()]);
    $account->save();
    $this->container->get('current_user')->setAccount($account);

    // Create new group type.
    $group_type_id = 'custom';
    /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    $this->container->get('entity_type.manager')
      ->getStorage('group_type')
      ->create([
        'id' => $group_type_id,
        'label' => 'Custom group',
        'description' => 'Custom group',
        'creator_wizard' => FALSE,
        'creator_membership' => TRUE,
      ])->save();
  }

  /**
   * Tests the group members overview.
   */
  public function testGroupMembersOverview() {
    $view = Views::getView('group_members_extra');
    $view->setDisplay();

    /* @var \Drupal\group\Entity\GroupInterface $group1 */
    $group1 = Group::create([
      'type' => 'custom',
      'label' => $this->randomMachineName(),
    ]);
    $group1->save();

    /* @var \Drupal\group\Entity\GroupInterface $group2 */
    $group2 = Group::create([
      'type' => 'custom',
      'label' => $this->randomMachineName(),
    ]);
    $group2->save();

    /** @var \Drupal\Core\Session\AccountInterface $account2 */
    $account2 = User::create(['name' => 'user2']);
    $account2->save();

    $account2 = $this->container->get('entity_type.manager')->getStorage('user')->loadByProperties(
      ['name' => 'user2']
    );

    $view->preview('page_1', [$group2->id()]);

    // Verify that the filtering works.
    $this->assertEquals(1, count($view->result), 'Found the expected number of results.');
    $view->destroy();

    // Verify multiple members are shown.
    $group2->addMember(current($account2));
    $view->preview('page_1', [$group2->id()]);
    $this->assertEquals(2, count($view->result), 'Found the expected number of results.');
    $view->destroy();

    $view->preview('default', [22]);
    $this->assertEquals(0, count($view->result), 'Found the expected number of results.');
  }

  /**
   * Tests the group contacts overview.
   */
  public function testGroupContacts() {
    $view = Views::getView('group_contacts');
    $view->setDisplay();

    /* @var \Drupal\group\Entity\GroupInterface $group1 */
    $group1 = Group::create([
      'type' => 'custom',
      'label' => $this->randomMachineName(),
    ]);
    $group1->save();

    /* @var \Drupal\group\Entity\GroupInterface $group2 */
    $group2 = Group::create([
      'type' => 'custom',
      'label' => $this->randomMachineName(),
    ]);
    $group2->save();

    /** @var \Drupal\Core\Session\AccountInterface $account2 */
    $account2 = User::create(['name' => 'user2']);
    $account2->save();

    $account2 = $this->container->get('entity_type.manager')->getStorage('user')->loadByProperties(
      ['name' => 'user2']
    );

    $view->preview('block_1', [$group2->id()]);

    // Verify that the filtering works.
    $this->assertEquals(0, count($view->result), 'Found the expected number of results.');
    $view->destroy();

    // Verify correct member is shown.
    $group2->addMember(current($account2), ['group_contact' => TRUE]);

    $view->preview('block_1', [$group2->id()]);
    $this->assertEquals(1, count($view->result), 'Found the expected number of results.');
    $this->assertEqual($view->getTitle(), 'Custom group contacts', 'Title is as expected.');
    $view->destroy();

    $view->preview('default', [22]);
    $this->assertEquals(0, count($view->result), 'Found the expected number of results.');
  }

}
