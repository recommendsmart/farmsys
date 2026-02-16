<?php

namespace Drupal\Tests\group_notifications\Functional;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\Tests\group\Functional\GroupBrowserTestBase;

/**
 * Tests the group membership emails.
 *
 * @group group_notifications
 */
class GroupMembershipMailTest extends GroupBrowserTestBase {

  use AssertMailTrait {
    getMails as drupalGetMails;
  }

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'group',
    'group_test_config',
    'group_notifications',
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
  protected function setUp() {
    parent::setUp();
    $permissions = $this->getGlobalPermissions();
    $permissions[] = 'administer group';

    $this->account = $this->createUser($permissions);
    $this->group = $this->createGroup(['uid' => $this->account->id()]);

    $this->member = $this->createUser([
      'access group overview',
    ]);
  }

  /**
   * Tests that a group member gets email when membership is removed/added.
   */
  public function testMembershipEmailNotification() {

    $this->group->addMember($this->member);
    $this->group->removeMember($this->member);
    self::assertCount(0, $this->drupalGetMails(), 'Notification emails not sent');

    // Now enable the notification.
    $this->config('group_notifications.mail')
      ->set('membership_added.enabled', TRUE)
      ->set('membership_removed.enabled', TRUE)
      ->save();

    $this->group->addMember($this->member);
    $this->group->removeMember($this->member);
    self::assertCount(2, $this->drupalGetMails(), 'Notification emails sent');

  }

}
