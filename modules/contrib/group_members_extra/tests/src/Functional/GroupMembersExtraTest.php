<?php

namespace Drupal\Tests\group_members_extra\Functional;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\Tests\group\Functional\GroupBrowserTestBase;

/**
 * Tests the group members extra functionality.
 *
 * @group group_members_extra
 */
class GroupMembersExtraTest extends GroupBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'group',
    'group_members_extra',
  ];

  /**
   * The group we will use to test methods on.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $group;

  /**
   * The group administrator user we will use.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $account;

  /**
   * The group member user we will use.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $member;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUp(): void {
    parent::setUp();

    $permissions = $this->getGlobalPermissions();
    $permissions[] = 'administer group';

    $this->account = $this->createUser($permissions);

    // Create new group type.
    $group_type_id = 'group_contact';
    /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    $this->entityTypeManager
      ->getStorage('group_type')
      ->create([
        'id' => $group_type_id,
        'label' => 'Group with contacts',
        'description' => 'Group with contacts',
        'creator_wizard' => FALSE,
        'creator_membership' => FALSE,
      ])->save();
    $this->group = $this->createGroup(['uid' => $this->account->id(), 'type' => $group_type_id]);

    $this->member = $this->createUser([
      'access group overview',
    ]);
  }

  /**
   * Tests that a group member can be marked as contact person.
   */
  public function testMemberIsContactPerson(): void {

    $member_role = $this->group->getGroupType()->getMemberRole();
    $permissions = ['view group', 'administer members'];
    $member_role->grantPermissions($permissions)->trustData()->save();

    $this->group->addMember($this->member);
    $this->drupalLogin($this->member);

    $this->drupalGet('/group/1');
    $this->drupalGet('/group/1/content/1/edit');
    $page = $this->getSession()->getPage();
    $page->selectFieldOption('group_contact[value]', TRUE);
    $this->submitForm([], 'Save');
    $this->assertSession()->statusCodeEquals(200);

  }

}
