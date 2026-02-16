<?php

namespace Drupal\Tests\group_members_extra\Functional;

use Drupal\Tests\group\Functional\GroupBrowserTestBase;

/**
 * Tests the group operations blocks.
 *
 * @group block
 */
class GroupOperationsBlocksTest extends GroupBrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'group', 'gnode', 'group_members_extra'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

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
   * The non member user we will use.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $non_member;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $permissions = $this->getGlobalPermissions();
    $permissions[] = 'administer group';
    $permissions[] = 'administer blocks';
    $this->account = $this->createUser($permissions);

    $this->drupalLogin($this->account);

    // Place the blocks in the content area
    $block_url = 'admin/structure/block/add/group_membership_operations/classy';
    $edit = [
      'region' => 'content',
      'settings[context_mapping][group]' => '@group.group_route_context:group',
    ];
    $this->drupalPostForm($block_url, $edit, 'Save block');
    $block_url = 'admin/structure/block/add/group_content_operations/classy';
    $this->drupalPostForm($block_url, $edit, 'Save block');

    // Create new group type.
    $group_type_id = 'operations';
    /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    $this->entityTypeManager
      ->getStorage('group_type')
      ->create([
        'id' => $group_type_id,
        'label' => 'Operations',
        'description' => 'Operations',
        'creator_wizard' => FALSE,
        'creator_membership' => FALSE,
      ])->save();
    $this->group = $this->createGroup([
      'uid' => $this->account->id(),
      'type' => $group_type_id
    ]);

    $group_type = $this->group->getGroupType();

    // Add the article content type to the group type.
    $this->createContentType(['type' => 'article']);
    /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    $storage->createFromPlugin($group_type, 'group_node:article')->save();

    $outsider_role = $group_type->getOutsiderRole();
    $permissions = ['view group', 'view group members', 'join group'];
    $outsider_role->grantPermissions($permissions)->trustData()->save();

    $member_role = $group_type->getMemberRole();
    $permissions = [
      'view group',
      'view group members',
      'leave group',
      'create group_node:article entity',
    ];
    $member_role->grantPermissions($permissions)->trustData()->save();

    $this->non_member = $this->createUser([
      'access group overview',
    ]);

    $this->member = $this->createUser([
      'access group overview',
    ]);
    $this->group->addMember($this->member);
  }

  /**
   * Test both operations blocks.
   */
  public function testOperationsBlocks() {
    $this->drupalLogin($this->non_member);
    $this->drupalGet('/group/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Group membership operations');
    $this->assertSession()->linkExists('Join group');
    $this->assertSession()->pageTextNotContains('Group content operations');

    $this->drupalLogin($this->member);
    $this->drupalGet('/group/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Group membership operations');
    $this->assertSession()->linkExists('Leave group');
    $this->assertSession()->pageTextContains('Group content operations');
    $this->assertSession()->linkExists('Add article');
  }

}
