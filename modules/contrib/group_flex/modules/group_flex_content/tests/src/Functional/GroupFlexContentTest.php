<?php

namespace Drupal\Tests\group_flex_content\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group_flex\Plugin\GroupVisibilityInterface;
use Drupal\Tests\group\Functional\GroupBrowserTestBase;

/**
 * Tests the behavior of the group flexible content.
 *
 * @group group
 */
class GroupFlexContentTest extends GroupBrowserTestBase {

  use StringTranslationTrait;

  /**
   * Set strict config schema.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'group',
    'group_test_config',
    'group_flex',
    'group_permissions',
    'group_flex_content',
    'group_flex_content_test',
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
      'bypass group access',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->account = $this->createUser($this->getGlobalPermissions());
    $this->group = $this->createGroup(['uid' => $this->account->id()]);
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    // Add the anonymous content type.
    $this->createContentType(['type' => 'anonymous']);
    // Add the outsider content type.
    $this->createContentType(['type' => 'outsider']);
    // Add the member content type.
    $this->createContentType(['type' => 'member']);
    // Add the flexible content type.
    $this->createContentType(['type' => 'flexible']);
    // Add the contentvisibility content type.
    $this->createContentType(['type' => 'contentvisibility']);
  }

  /**
   * Tests group flex content type configuration.
   */
  public function testGroupFlexContentType() {
    $this->drupalLogin($this->account);

    // Now change the settings to enabled and public.
    $this->drupalGet('/admin/group/types/manage/default');
    $page = $this->getSession()->getPage();
    $page->selectFieldOption('group_flex_enabler', TRUE);
    $page->selectFieldOption('group_type_visibility', GroupVisibilityInterface::GROUP_FLEX_TYPE_VIS_PRIVATE);
    $this->submitForm([], 'Save group type');
    $this->assertSession()->statusCodeEquals(200);

    // Now check the outsider plugin install page.
    $this->drupalGet('/admin/group/content/install/default/group_node%3Aoutsider');
    $this->assertSession()->fieldDisabled('edit-group-content-visibility-flexible');

    // Now change the group visibility to public.
    $this->drupalGet('/admin/group/types/manage/default');
    $page = $this->getSession()->getPage();
    $page->selectFieldOption('group_type_visibility', GroupVisibilityInterface::GROUP_FLEX_TYPE_VIS_PUBLIC);
    $this->submitForm([], 'Save group type');
    $this->assertSession()->statusCodeEquals(200);

    // Now install the anonymous plugin.
    $this->drupalGet('/admin/group/content/install/default/group_node%3Aanonymous');
    $this->assertSession()->fieldEnabled('edit-group-content-visibility-flexible');
    $page->selectFieldOption('group_content_visibility', 'anonymous');
    $this->submitForm([], 'Install plugin');
    $this->assertSession()->statusCodeEquals(200);

    // Now install the outsider plugin.
    $this->drupalGet('/admin/group/content/install/default/group_node%3Aoutsider');
    $this->assertSession()->fieldEnabled('edit-group-content-visibility-flexible');
    $page->selectFieldOption('group_content_visibility', 'outsider');
    $this->submitForm([], 'Install plugin');
    $this->assertSession()->statusCodeEquals(200);

    // Now install the member plugin.
    $this->drupalGet('/admin/group/content/install/default/group_node%3Amember');
    $this->assertSession()->fieldEnabled('edit-group-content-visibility-flexible');
    $page->selectFieldOption('group_content_visibility', 'member');
    $this->submitForm([], 'Install plugin');
    $this->assertSession()->statusCodeEquals(200);

    // Now install the flexible plugin.
    $this->drupalGet('/admin/group/content/install/default/group_node%3Aflexible');
    $this->assertSession()->fieldEnabled('edit-group-content-visibility-flexible');
    $page->selectFieldOption('group_content_visibility', 'flexible');
    $this->submitForm([], 'Install plugin');
    $this->assertSession()->statusCodeEquals(200);

    // Create an "anonymous" node.
    $this->drupalGet('/group/1/content/create/group_node%3Aanonymous');
    $edit = [
      'title[0][value]' => 'Anonymous node',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Create an "outsider" node.
    $this->drupalGet('/group/1/content/create/group_node%3Aoutsider');
    $edit = [
      'title[0][value]' => 'Outsider node',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Create a "member" node.
    $this->drupalGet('/group/1/content/create/group_node%3Amember');
    $edit = [
      'title[0][value]' => 'Member node',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Create a "flexible" node with visibility "anonymous".
    $this->drupalGet('/group/1/content/create/group_node%3Aflexible');
    $edit = [
      'title[0][value]' => 'Flexible anonymous node',
      'content_visibility' => 'anonymous',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Create a "flexible" node with visibility "outsider".
    $this->drupalGet('/group/1/content/create/group_node%3Aflexible');
    $edit = [
      'title[0][value]' => 'Flexible outsider node',
      'content_visibility' => 'outsider',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Create a "flexible" node with visibility "member".
    $this->drupalGet('/group/1/content/create/group_node%3Aflexible');
    $edit = [
      'title[0][value]' => 'Flexible member node',
      'content_visibility' => 'member',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Open all the pages as anonymous user.
    $this->drupalLogout();
    $this->drupalGet('/node/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/node/2');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/node/3');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/node/4');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/node/5');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/node/6');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/all-content');
    $this->assertSession()->pageTextContains('Anonymous node');
    $this->assertSession()->pageTextNotContains('Outsider node');
    $this->assertSession()->pageTextNotContains('Member node');
    $this->assertSession()->pageTextContains('Flexible anonymous node');
    $this->assertSession()->pageTextNotContains('Flexible outsider node');
    $this->assertSession()->pageTextNotContains('Flexible member node');

    // Now open all the nodes as outsider.
    $user2 = $this->createUser(['access content']);
    $this->drupalLogin($user2);
    $this->drupalGet('/node/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/node/2');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/node/3');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/node/4');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/node/5');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/node/6');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/all-content');
    $this->assertSession()->pageTextContains('Anonymous node');
    $this->assertSession()->pageTextContains('Outsider node');
    $this->assertSession()->pageTextNotContains('Member node');
    $this->assertSession()->pageTextContains('Flexible anonymous node');
    $this->assertSession()->pageTextContains('Flexible outsider node');
    $this->assertSession()->pageTextNotContains('Flexible member node');

    // Add the outsider to the group and open all nodes as a group member.
    $this->group->addMember($user2);
    $this->drupalGet('/node/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/node/2');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/node/3');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/node/4');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/node/5');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/node/6');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/all-content');
    $this->assertSession()->pageTextContains('Anonymous node');
    $this->assertSession()->pageTextContains('Outsider node');
    $this->assertSession()->pageTextContains('Member node');
    $this->assertSession()->pageTextContains('Flexible anonymous node');
    $this->assertSession()->pageTextContains('Flexible outsider node');
    $this->assertSession()->pageTextContains('Flexible member node');

  }

  /**
   * Tests group flex content visibility permissions.
   */
  public function testGroupFlexContentVisibilityPermissions() {
    $this->drupalLogin($this->account);

    // Now change the settings to enabled and public.
    $this->drupalGet('/admin/group/types/manage/default');
    $page = $this->getSession()->getPage();
    $page->selectFieldOption('group_flex_enabler', TRUE);
    $page->selectFieldOption('group_type_visibility', GroupVisibilityInterface::GROUP_FLEX_TYPE_VIS_PUBLIC);
    $this->submitForm([], 'Save group type');
    $this->assertSession()->statusCodeEquals(200);

    // Now install the flexible plugin.
    $this->drupalGet('/admin/group/content/install/default/group_node%3Acontentvisibility');
    $page->selectFieldOption('group_content_visibility', 'flexible');
    $this->submitForm([], 'Install plugin');
    $this->assertSession()->statusCodeEquals(200);

    // Set the permissions for adding content to new users.
    $edit = [
      'default-member[create group_node:contentvisibility entity]' => 'create group_node:contentvisibility entity',
    ];
    $this->drupalPostForm('/admin/group/types/manage/default/permissions', $edit, 'Save permissions');

    // Add a normal user to the group.
    $user2 = $this->createUser(['access content']);
    $this->group->addMember($user2);
    $this->drupalLogin($user2);

    $this->drupalGet('/group/1/content/create/group_node%3Acontentvisibility');
    $this->assertSession()->optionExists('content_visibility', 'Any visitors of the website');
    $this->assertSession()->optionExists('content_visibility', 'Users registered on the website only');
    $this->assertSession()->optionExists('content_visibility', 'Members only');

    // Log in as the administrative user.
    $this->drupalLogin($this->account);
    // Revoke some of the permissions for setting content visibility options.
    $edit = [
      'default-member[use visibility anonymous for group_node:contentvisibility entity]' => '',
      'default-member[use visibility outsider for group_node:contentvisibility entity]' => '',
    ];
    $this->drupalPostForm('/admin/group/types/manage/default/permissions', $edit, 'Save permissions');

    // Log back in as the normal user.
    $this->drupalLogin($user2);

    // User should only be able to see the "members only" visibility option.
    $this->drupalGet('/group/1/content/create/group_node%3Acontentvisibility');
    $this->assertSession()->optionNotExists('content_visibility', 'Any visitors of the website');
    $this->assertSession()->optionNotExists('content_visibility', 'Users registered on the website only');
    $this->assertSession()->optionExists('content_visibility', 'Members only');
  }

}
