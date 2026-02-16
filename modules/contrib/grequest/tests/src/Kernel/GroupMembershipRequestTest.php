<?php

namespace Drupal\Tests\grequest\Kernel;

use Drupal\grequest\Plugin\GroupContentEnabler\GroupMembershipRequest;
use Drupal\group\GroupMembership;
use Drupal\Tests\group\Kernel\GroupKernelTestBase;

/**
 * Tests the general behavior of group entities.
 *
 * @coversDefaultClass \Drupal\group\Entity\Group
 * @group group
 */
class GroupMembershipRequestTest extends GroupKernelTestBase {

  /**
   * Membership request manager.
   *
   * @var Drupal\grequest\MembershipRequestManager
   */
  protected $membershipRequestManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group we will use to test methods on.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $group;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['grequest', 'state_machine', 'user', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['grequest', 'state_machine']);

    $this->membershipRequestManager = $this->container->get('grequest.membership_request_manager');
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->group = $this->createGroup();
    // Enable group membership request group content plugin.
    $group_content_type_storage = $this->entityTypeManager->getStorage('group_content_type');
    $group_content_type_storage->save($group_content_type_storage->createFromPlugin($this->group->getGroupType(), 'group_membership_request'));
  }

  /**
   * Test the creation of the membership request when user is the member.
   */
  public function testAddRequestForMember() {
    $account = $this->createUser();
    $this->group->addMember($account);

    $this->expectExceptionMessage('This user is already a member of the group');
    $this->membershipRequestManager->create($this->group, $account);
  }

  /**
   * Test validation of the membership request when user is the member.
   */
  public function testRequestWithMemberAsUser() {
    $account = $this->createUser();

    $group_membership_request = $this->membershipRequestManager->create($this->group, $account);
    $this->group->addMember($account);

    $violations = $group_membership_request->validate();
    $this->assertNotEqual(count($violations), 0);

    $messages = [];
    foreach ($violations as $violation) {
      $messages[] = $violation->getMessage()->getUntranslatedString();
    }

    $this->assert(in_array('User "%name" is already a member of group', $messages), 'The validation of group membership request is found.');
  }

  /**
   * Test status update.
   */
  public function testRequestStatusUpdate() {
    $account = $this->createUser();

    $group_membership_request = $this->membershipRequestManager->create($this->group, $account);
    $group_membership_request->save();

    $this->assertEqual($group_membership_request->grequest_status->value, GroupMembershipRequest::REQUEST_PENDING);

    $this->membershipRequestManager->approve($group_membership_request);
    $this->assertEqual($group_membership_request->grequest_status->value, GroupMembershipRequest::REQUEST_APPROVED);

    $this->membershipRequestManager->reject($group_membership_request);
    $this->assertEqual($group_membership_request->grequest_status->value, GroupMembershipRequest::REQUEST_REJECTED);
  }

  /**
   * Test approval with roles.
   */
  public function testApprovalWithRoles() {
    $account = $this->createUser();
    $role_name = 'default-custom';

    $group_membership_request = $this->membershipRequestManager->create($this->group, $account);
    $group_membership_request->save();

    $this->membershipRequestManager->approve($group_membership_request, [$role_name]);

    $group_membership = $this->group->getMember($account);
    $this->assert($group_membership instanceof GroupMembership, 'Group membership has been successfully created.');

    $this->assert(in_array($role_name, array_keys($group_membership->getRoles())), 'Role has been found');
  }

  /**
   * Test deletion of group membership request after user deletion.
   */
  public function testUserDeletion() {
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('user');

    $account = $this->createUser();
    $group_membership_request = $this->membershipRequestManager->create($this->group, $account);
    $group_membership_request->save();

    $this->entityTypeManager->getStorage('user')->delete([$account]);

    $membership_request = $this->membershipRequestManager->getMembershipRequest($account, $this->group);
    $this->assertNull($membership_request);
  }

  /**
   * Test deletion of group membership request after group membership deletion.
   */
  public function testGroupMembershipDeletion() {
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('user');

    $account = $this->createUser();

    $group_membership_request = $this->membershipRequestManager->create($this->group, $account);
    $group_membership_request->save();

    $this->group->addMember($account);

    $this->group->removeMember($account);

    $membership_request = $this->membershipRequestManager->getMembershipRequest($account, $this->group);
    $this->assertNull($membership_request);
  }

  /**
   * Test the user is accessible after request creation.
   */
  public function testUserAccessibility() {
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('user');

    $account = $this->createUser();

    $group_membership_request = $this->membershipRequestManager->create($this->group, $account);
    $group_membership_request->save();

    $this->group->addMember($account);

    $this->group->removeMember($account);

    $membership_request = $this->membershipRequestManager->getMembershipRequest($account, $this->group);
    $this->assertNull($membership_request);
  }

}
