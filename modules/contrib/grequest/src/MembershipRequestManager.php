<?php

namespace Drupal\grequest;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\grequest\Plugin\GroupContentEnabler\GroupMembershipRequest;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\user\UserInterface;

/**
 * Membership Request Manager class.
 */
class MembershipRequestManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * PrivacyManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * Get membership request.
   *
   * @param \Drupal\user\UserInterface $user
   *   User.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Group content.
   */
  public function getMembershipRequest(UserInterface $user, GroupInterface $group) {
    // If no responsible group content types were found, we return nothing.
    $group_content_type_storage = $this->entityTypeManager->getStorage('group_content_type');
    $group_content_types = $group_content_type_storage->loadByContentPluginId('group_membership_request');
    if (!empty($group_content_types)) {
      $group_content_storage = $this->entityTypeManager->getStorage('group_content');
      $group_content_items = $group_content_storage->loadByProperties([
        'type' => array_keys($group_content_types),
        'entity_id' => $user->id(),
        'gid' => $group->id(),
      ]);

      if (!empty($group_content_items)) {
        return reset($group_content_items);
      }
    }

    return NULL;
  }

  /**
   * Approve a membership request.
   *
   * @param Drupal\group\Entity\GroupContentInterface $group_content
   *   Group membership request group content.
   * @param array $group_roles
   *   Group roles to be added to a member.
   *
   * @return bool
   *   Result.
   */
  public function approve(GroupContentInterface $group_content, array $group_roles = []) {
    $this->updateStatus($group_content, GroupMembershipRequest::TRANSITION_APPROVE);
    $result = $group_content->save();
    if ($result) {
      // Adding user to a group.
      $group_content->getGroup()->addMember($group_content->getEntity(), [
        'group_roles' => $group_roles,
      ]);
    }

    return $result;
  }

  /**
   * Reject a membership request.
   *
   * @param Drupal\group\Entity\GroupContentInterface $group_content
   *   Group membership request group content.
   *
   * @return bool
   *   Result.
   */
  public function reject(GroupContentInterface $group_content) {
    $this->updateStatus($group_content, GroupMembershipRequest::TRANSITION_REJECT);
    return $group_content->save();
  }

  /**
   * Update status of a membership request.
   *
   * @param Drupal\group\Entity\GroupContentInterface $group_content
   *   Group membership request group content.
   * @param string $transition_id
   *   Transition approve | reject.
   *
   * @return bool
   *   Result.
   */
  public function updateStatus(GroupContentInterface $group_content, $transition_id) {
    $state_item = $group_content->get(GroupMembershipRequest::STATUS_FIELD)->first();
    $state_item->applyTransitionById($transition_id);
    $group_content->set('grequest_updated_by', $this->currentUser->id());
  }

  /**
   * Create group membership request group content.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   * @param \Drupal\user\UserInterface $user
   *   User.
   *
   * @return Drupal\group\Entity\GroupContentInterface
   *   Group membership request group content.
   */
  public function create(GroupInterface $group, UserInterface $user) {
    if ($group->getMember($user)) {
      throw new \Exception('This user is already a member of the group');
    }
    $group_content = GroupContent::create([
      'type' => $group
        ->getGroupType()
        ->getContentPlugin('group_membership_request')
        ->getContentTypeConfigId(),
      'gid' => $group->id(),
      'entity_id' => $user->id(),
      GroupMembershipRequest::STATUS_FIELD => GroupMembershipRequest::REQUEST_NEW,
    ]);

    // We have to set transition here. Once the group_content saved events will
    // be correctly fired.
    $this->updateStatus($group_content, GroupMembershipRequest::TRANSITION_CREATE);

    return $group_content;
  }

}
