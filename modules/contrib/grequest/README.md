# About

Group Membership Request - This module extends group module and allows user to request membership to the group.

# Installation

You can install this module using standard user interface or "drush en grequest" command

# Configuration

 1) Install Group content type "Group Membership Request" - /admin/group/types/manage/[ YOUR_GROUP_TYPE ]/content

 2) Provide a permission "Request group membership" to the outsider role - /admin/group/types/manage/[ YOUR_GROUP_TYPE ]/permissions

 3) Users who are not members of the group should see a link in group operations block to request group membership

 4) Check to see /group/[ GROUP ID ]/members-pending, to approve or to reject all pending membership requests. To access this page you need to have "administer membership requests" permission.


#  Development

1) To add a new request in the code use
```
\Drupal::service('grequest.membership_request_manager')->create($group, $user);
```

2) To approve a new request in the code use
```
\Drupal::service('grequest.membership_request_manager')->approve($group_content_request_membership);
```

3) To reject a new request in the code use
```
\Drupal::service('grequest.membership_request_manager')->approve($group_content_request_membership);
```

4) To get the request fot the user

```
\Drupal::service('grequest.membership_request_manager')->getMembershipRequest($user, $group);
```

# Events

For creation

```
group_membership_request.create.pre_transition

group_membership_request.create.post_transition
```

For approval

```
group_membership_request.approve.pre_transition

group_membership_request.approve.post_transition
```

For rejection

```
group_membership_request.reject.pre_transition

group_membership_request.reject.post_transition
```

An example
```
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class MyEventSubscriber implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      'group_membership_request.create.pre_transition' => 'preCreateTransition',
      'group_membership_request.create.post_transition' => 'postCreateTransition',
      'group_membership_request.approve.pre_transition' => 'preApproveTransition',
      'group_membership_request.approve.post_transition' => 'postApproveTransition',
      'group_membership_request.reject.pre_transition' => 'preRejectTransition',
      'group_membership_request.reject.post_transition' => 'postRejectTransition',
    ];
  }

  public function preCreateTransition(WorkflowTransitionEvent $event) {
    $this->setMessage($event, __FUNCTION__);
  }

  public function postCreateTransition(WorkflowTransitionEvent $event) {
    $this->setMessage($event, __FUNCTION__);
  }

  public function preApproveTransition(WorkflowTransitionEvent $event) {
    $this->setMessage($event, __FUNCTION__);
  }

  public function postApproveTransition(WorkflowTransitionEvent $event) {
    $this->setMessage($event, __FUNCTION__);
  }

  public function preRejectTransition(WorkflowTransitionEvent $event) {
    $this->setMessage($event, __FUNCTION__);
  }

  public function postRejectTransition(WorkflowTransitionEvent $event) {
    $this->setMessage($event, __FUNCTION__);
  }

  protected function setMessage(WorkflowTransitionEvent $event, $phase) {
    \Drupal::messenger()->addMessage(new TranslatableMarkup('@entity_label (@field_name) - @state_label at @phase (workflow: @workflow).', [
      '@entity_label' => $event->getEntity()->label(),
      '@field_name' => $event->getFieldName(),
      '@state_label' => $event->getTransition()->getToState()->getLabel(),
      '@workflow' => $event->getWorkflow()->getId(),
      '@phase' => $phase,
    ]));
  }

}

```
