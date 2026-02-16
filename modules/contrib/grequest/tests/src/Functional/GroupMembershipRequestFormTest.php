<?php

namespace Drupal\Tests\grequest\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\group\Entity\GroupContent;
use Drupal\group\GroupMembership;
use Drupal\Tests\group\Functional\GroupBrowserTestBase;

/**
 * Tests the behavior of the group type form.
 *
 * @group group
 */
class GroupMembershipRequestFormTest extends GroupBrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group', 'group_test_config', 'state_machine', 'grequest'];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Membership request manager.
   *
   * @var Drupal\grequest\MembershipRequestManager
   */
  protected $membershipRequestManager;

  /**
   * The group we will use to test methods on.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $group;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->group = $this->createGroup();
    $this->membershipRequestManager = $this->container->get('grequest.membership_request_manager');
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    // Install group_membership_request group content.
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    $config = [
      'group_cardinality' => 0,
      'entity_cardinality' => 1,
    ];

    $storage->createFromPlugin($this->group->getGroupType(), 'group_membership_request', $config)->save();

    // Add a text field to the group content type.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_test_text',
      'entity_type' => 'group_content',
      'type' => 'text',
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $this->group
        ->getGroupType()
        ->getContentPlugin('group_membership_request')
        ->getContentTypeConfigId(),
      'label' => 'String long',
    ])->save();

    EntityFormDisplay::create([
      'targetEntityType' => 'group_content',
      'bundle' => $this->group
        ->getGroupType()
        ->getContentPlugin('group_membership_request')
        ->getContentTypeConfigId(),
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('field_test_text', ['type' => 'text_textfield'])->enable()->save();

    // Add permissions to the creator of the group.
    $role = $this->group->getGroupType()->getMemberRole();
    $role->grantPermissions(['administer membership requests']);
    $role->save();

    // Allow outsider request membership.
    $role = $this->group->getGroupType()->getOutsiderRole();
    $role->grantPermissions(['request group membership']);
    $role->save();
  }

  /**
   * Tests approval form.
   */
  public function testApprovalForm() {
    $account = $this->createUser();
    $group_membership_request = $this->membershipRequestManager->create($this->group, $account);
    $group_membership_request->save();
    $role_name = 'default-custom';

    $this->drupalGet("/group/{$this->group->id()}/content/{$group_membership_request->id()}/approve-membership");
    $this->assertSession()->statusCodeEquals(200);

    $submit_button = 'Approve';
    $this->assertSession()->buttonExists($submit_button);
    $this->assertSession()->linkExists('Cancel');
    $this->assertSession()->linkByHrefExists($this->group->toUrl()->toString());
    $this->assertSession()->pageTextContains(strip_tags($this->t('Are you sure you want to approve a request for %user?', ['%user' => $account->getDisplayName()])->render()));

    $edit = [
      "roles[$role_name]" => 1,
    ];

    $this->submitForm($edit, $submit_button);
    $this->assertSession()->pageTextContains($this->t('Membership request approved'));

    $group_membership = $this->group->getMember($account);
    $this->assert($group_membership instanceof GroupMembership, 'Group membership has been successfully created.');

  }

  /**
   * Tests reject form.
   */
  public function testRejectForm() {
    $account = $this->createUser();
    $group_membership_request = $this->membershipRequestManager->create($this->group, $account);
    $group_membership_request->save();

    $this->drupalGet("/group/{$this->group->id()}/content/{$group_membership_request->id()}/reject-membership");
    $this->assertSession()->statusCodeEquals(200);

    $submit_button = 'Reject';
    $this->assertSession()->buttonExists($submit_button);
    $this->assertSession()->linkExists('Cancel');
    $this->assertSession()->linkByHrefExists($this->group->toUrl()->toString());
    $this->assertSession()->pageTextContains(strip_tags($this->t('Are you sure you want to reject a request for %user?', ['%user' => $account->getDisplayName()])->render()));

    $this->submitForm([], $submit_button);
    $this->assertSession()->pageTextContains($this->t('Membership request rejected'));

    $group_membership = $this->group->getMember($account);
    $this->assertFalse($group_membership, 'Group membership was not found.');
  }

  /**
   * Tests request form.
   */
  public function testRequestForm() {
    // Access request form as a member.
    $this->drupalGet("/group/{$this->group->id()}/request-membership");
    $this->assertSession()->statusCodeEquals(403);

    // Access request form as a not member.
    $account = $this->createUser();
    $this->drupalLogin($account);

    $this->drupalGet("/group/{$this->group->id()}/request-membership");
    $this->assertSession()->statusCodeEquals(200);

    $submit_button = 'Request group membership';
    $this->assertSession()->buttonExists($submit_button);
    $this->assertSession()->linkExists('Cancel');
    $this->assertSession()->linkByHrefExists($this->group->toUrl()->toString());
    $this->assertSession()->pageTextContains(strip_tags($this->t('Request membership for group %group', ['%group' => $this->group->label()])->render()));
    $this->assertSession()->fieldExists('field_test_text[0][value]');
    $membership_request = $this->membershipRequestManager->getMembershipRequest($account, $this->group);
    $this->assertNull($membership_request);

    $this->submitForm([], $submit_button);
    $this->assertSession()->pageTextContains($this->t('Your request is waiting for approval'));

    $membership_request = $this->membershipRequestManager->getMembershipRequest($account, $this->group);
    $this->assert($membership_request instanceof GroupContent, 'Membership request has been successfully created.');

    $this->drupalGet("/group/{$this->group->id()}/request-membership");
    $this->submitForm([], $submit_button);

    $this->assertSession()->pageTextContains($this->t('You have already sent a request')->render());

  }

  /**
   * Tests request after leave of the group.
   */
  public function testRequestAfterLeaveForm() {
    $account = $this->createUser();
    $this->drupalLogin($account);

    // Request membership and approve it.
    $this->drupalGet("/group/{$this->group->id()}/request-membership");
    $this->submitForm([], 'Request group membership');
    $this->assertSession()->pageTextContains($this->t('Your request is waiting for approval'));

    $membership_request = $this->membershipRequestManager->getMembershipRequest($account, $this->group);
    $this->membershipRequestManager->approve($membership_request);

    // Leave the group.
    $this->drupalGet("/group/{$this->group->id()}/leave");
    $this->submitForm([], 'Leave group');

    // Try to request membership again.
    $this->drupalGet("/group/{$this->group->id()}/request-membership");
    $this->submitForm([], 'Request group membership');
    $this->assertSession()->pageTextContains($this->t('Your request is waiting for approval'));

  }

  /**
   * Test that the user is still accessible after request creation.
   */
  public function testUserAccessibility() {
    $account = $this->createUser();
    $this->drupalLogin($account);

    // Request membership and approve it.
    $this->drupalGet("/group/{$this->group->id()}/request-membership");
    $this->submitForm([], 'Request group membership');
    $this->assertSession()->pageTextContains($this->t('Your request is waiting for approval'));

    // Leave the group.
    $this->drupalGet("/user/{$account->id()}");
    $this->assertSession()->statusCodeEquals(200);

  }

}
