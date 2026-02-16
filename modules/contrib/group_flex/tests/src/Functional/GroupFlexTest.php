<?php

namespace Drupal\Tests\group_flex\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group_flex\Plugin\GroupVisibilityInterface;
use Drupal\Tests\group\Functional\GroupBrowserTestBase;

/**
 * Tests the behavior of the group type form.
 *
 * @group group
 */
class GroupFlexTest extends GroupBrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'group',
    'group_test_config',
    'group_flex',
    'group_permissions',
    'group_flex_test',
  ];

  /**
   * The entity type manager service.
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
   * The normal user we will use.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $account;

  /**
   * Gets the global (site) permissions for the group creator.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getGlobalPermissions() {
    return [
      'view the administration theme',
      'access administration pages',
      'access group overview',
      'create default group',
      'create other group',
      'administer group',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->account = $this->createUser([
      'administer group',
      'create default group',
    ]);
    $this->group = $this->createGroup(['uid' => $this->account->id()]);
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    // Add permissions to members of the group.
    $role = $this->group->getGroupType()->getMemberRole();
    $role->grantPermissions(['edit group']);
    $role->save();

  }

  /**
   * Tests group flex override group Public unjoinable functionality.
   */
  public function testGroupFlexGroupPublicUnjoinable() {
    $this->drupalLogin($this->groupCreator);
    $this->createFlexGroup();
    $this->drupalLogout();

    $this->drupalLogin($this->createUser(['create flexible_group group']));

    // Create a public group you can't join.
    $this->drupalGet('/group/add/flexible_group');
    $page = $this->getSession()->getPage();
    $page->fillField('Title', 'Flex group - public - unjoinable');
    $page->selectFieldOption('group_joining_methods[fake_button]', TRUE);
    $page->selectFieldOption('group_joining_methods[join_button]', FALSE);
    $this->submitForm([], 'edit-submit');

    $this->drupalLogout();

    $user2 = $this->createUser(['access group overview']);
    $this->drupalLogin($user2);

    $this->drupalGet('/admin/group');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Groups');
    $this->assertSession()->pageTextContains('Flex group - public - unjoinable');

    $this->drupalGet('/admin/group');
    $this->clickLink('Flex group - public - unjoinable');
    $join_link = $page->getSession()->getCurrentUrl() . '/join';
    $this->drupalGet($join_link);
    $this->assertSession()->statusCodeEquals(403);

  }

  /**
   * Tests group flex override group Private functionality.
   */
  public function testGroupFlexGroupPrivate() {
    $this->drupalLogin($this->groupCreator);
    $this->createFlexGroup();
    $this->drupalLogout();

    $this->drupalLogin($this->createUser(['create flexible_group group']));

    // Create private group.
    $this->drupalGet('/group/add/flexible_group');
    $this->assertSession()->fieldEnabled('group_visibility');
    $page = $this->getSession()->getPage();
    $page->selectFieldOption('group_visibility', GroupVisibilityInterface::GROUP_FLEX_TYPE_VIS_PRIVATE);
    $page->fillField('Title', 'Flex group - private');
    $this->submitForm([], 'edit-submit');
    $this->assertSession()->pageTextContains('Flex group - private');

    $this->drupalLogout();

    $user2 = $this->createUser(['access group overview']);
    $this->drupalLogin($user2);

    $this->drupalGet('/admin/group');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Groups');
    $this->assertSession()->pageTextNotContains('Flex group - private');

  }

  /**
   * Tests group open/closed groups.
   */
  public function testGroupFlexGroupOpenClosed() {
    $this->drupalLogin($this->groupCreator);

    // Now create a public open group.
    $this->drupalGet('/admin/group/types/add');
    $page = $this->getSession()->getPage();
    $page->fillField('label', 'Public open group');
    $page->fillField('id', 'public_open_group');
    $page->selectFieldOption('creator_wizard', FALSE);
    $page->selectFieldOption('group_flex_enabler', TRUE);
    $page->selectFieldOption('group_type_visibility', GroupVisibilityInterface::GROUP_FLEX_TYPE_VIS_PUBLIC);
    $page->selectFieldOption('group_type_joining_method[join_button]', TRUE);
    $page->selectFieldOption('group_type_joining_method[fake_button]', FALSE);
    $page->selectFieldOption('group_type_joining_method_override', FALSE);
    $this->submitForm([], 'Save group type');
    $this->assertSession()->statusCodeEquals(200);

    // Now create a public closed group.
    $this->drupalGet('/admin/group/types/add');
    $page = $this->getSession()->getPage();
    $page->fillField('label', 'Public closed group');
    $page->fillField('id', 'public_closed_group');
    $page->selectFieldOption('creator_wizard', FALSE);
    $page->selectFieldOption('group_flex_enabler', TRUE);
    $page->selectFieldOption('group_type_visibility', GroupVisibilityInterface::GROUP_FLEX_TYPE_VIS_PUBLIC);
    $page->selectFieldOption('group_type_joining_method[join_button]', FALSE);
    $page->selectFieldOption('group_type_joining_method[fake_button]', FALSE);
    $page->selectFieldOption('group_type_joining_method_override', FALSE);
    $this->submitForm([], 'Save group type');
    $this->assertSession()->statusCodeEquals(200);

    // Now create a private closed group.
    $this->drupalGet('/admin/group/types/add');
    $page = $this->getSession()->getPage();
    $page->fillField('label', 'Private closed group');
    $page->fillField('id', 'private_closed_group');
    $page->selectFieldOption('creator_wizard', FALSE);
    $page->selectFieldOption('group_flex_enabler', TRUE);
    $page->selectFieldOption('group_type_visibility', GroupVisibilityInterface::GROUP_FLEX_TYPE_VIS_PRIVATE);
    $page->selectFieldOption('group_type_joining_method[join_button]', FALSE);
    $page->selectFieldOption('group_type_joining_method[fake_button]', FALSE);
    $page->selectFieldOption('group_type_joining_method_override', FALSE);
    $this->submitForm([], 'Save group type');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogout();
    $this->drupalLogin($this->createUser([
      'create public_open_group group',
      'create public_closed_group group',
      'create private_closed_group group',
    ]));

    // Create public open group.
    $this->drupalGet('/group/add/public_open_group');
    $page = $this->getSession()->getPage();
    $page->fillField('Title', 'Open group - public');
    $this->submitForm([], 'edit-submit');

    // Create public closed group.
    $this->drupalGet('/group/add/public_closed_group');
    $page = $this->getSession()->getPage();
    $page->fillField('Title', 'Closed group - public');
    $this->submitForm([], 'edit-submit');

    // Create private closed group.
    $this->drupalGet('/group/add/private_closed_group');
    $page = $this->getSession()->getPage();
    $page->fillField('Title', 'Closed group - private');
    $this->submitForm([], 'edit-submit');

    $this->drupalLogout();

    $user2 = $this->createUser(['access group overview']);
    $this->drupalLogin($user2);

    $this->drupalGet('/admin/group');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Groups');
    $this->assertSession()->pageTextContains('Open group - public');
    $this->assertSession()->pageTextContains('Closed group - public');
    $this->assertSession()->pageTextNotContains('Closed group - private');

    $this->clickLink('Open group - public');
    $join_link = $page->getSession()->getCurrentUrl() . '/join';
    $this->drupalGet($join_link);
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('/admin/group');
    $this->clickLink('Closed group - public');
    $join_link = $page->getSession()->getCurrentUrl() . '/join';
    $this->drupalGet($join_link);
    $this->assertSession()->statusCodeEquals(403);

  }

  /**
   * Tests group flex group creator group..
   */
  public function testGroupFlexGroupCreatorWizardGroup() {
    $this->drupalLogin($this->groupCreator);

    // Now create a public open group.
    $this->drupalGet('/admin/group/types/add');
    $page = $this->getSession()->getPage();
    $page->fillField('label', 'Group creator group');
    $page->fillField('id', 'group_creator_group');
    $page->selectFieldOption('creator_wizard', TRUE);
    $page->selectFieldOption('group_flex_enabler', TRUE);
    $page->selectFieldOption('group_type_visibility', GroupVisibilityInterface::GROUP_FLEX_TYPE_VIS_PUBLIC);
    $page->selectFieldOption('group_type_joining_method[join_button]', TRUE);
    $page->selectFieldOption('group_type_joining_method[fake_button]', FALSE);
    $page->selectFieldOption('group_type_joining_method_override', TRUE);
    $this->submitForm([], 'Save group type');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogout();
    $this->drupalLogin($this->createUser([
      'create group_creator_group group',
    ]));

    // Create public open group.
    $this->drupalGet('/group/add/group_creator_group');
    $page = $this->getSession()->getPage();
    $page->fillField('Title', 'Group creator group');
    $page->selectFieldOption('group_joining_methods[join_button]', TRUE);
    $this->submitForm([], 'edit-submit');
    // Fill in step 2.
    $page = $this->getSession()->getPage();
    $this->submitForm([], 'edit-submit');

    $this->drupalLogout();

    // Test that the group is successfully created.
    $user2 = $this->createUser(['access group overview']);
    $this->drupalLogin($user2);

    $this->drupalGet('/admin/group');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Group creator group');

    $this->clickLink('Group creator group');
    $join_link = $page->getSession()->getCurrentUrl() . '/join';
    $this->drupalGet($join_link);
    $this->assertSession()->statusCodeEquals(200);

  }

  /**
   * Create new flexible group.
   */
  public function createFlexGroup() {

    // Now create a flexible type group.
    $this->drupalGet('/admin/group/types/add');
    $page = $this->getSession()->getPage();
    $page->fillField('label', 'Flexible group');
    $page->fillField('id', 'flexible_group');
    $page->selectFieldOption('creator_wizard', FALSE);
    $page->selectFieldOption('group_flex_enabler', TRUE);
    $page->selectFieldOption('group_type_visibility', GroupVisibilityInterface::GROUP_FLEX_TYPE_VIS_FLEX);
    $page->selectFieldOption('group_type_joining_method[join_button]', TRUE);
    $page->selectFieldOption('group_type_joining_method[fake_button]', TRUE);
    $page->selectFieldOption('group_type_joining_method_override', TRUE);

    $this->submitForm([], 'Save group type');
    $this->assertSession()->statusCodeEquals(200);

  }

}
