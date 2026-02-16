<?php

/**
 * @file
 * Views related hooks.
 */

/**
 * Implements hook_views_data().
 */
function grequest_views_data() {
  $data['groups']['request_membership'] = [
    'title' => t('Request Membership'),
    'help' => t('Provides a link to request membership to the group.'),
    'field' => [
      'id' => 'group_request_membership',
    ],
  ];

  $data['group_content']['approve_membership_request'] = [
    'field' => [
      'title' => t('Approve membership'),
      'help' => t('Provide a link to approve membership request.'),
      'id' => 'approve_membership_request',
    ],
  ];

  $data['group_content']['reject_membership_request'] = [
    'field' => [
      'title' => t('Reject membership'),
      'help' => t('Provide a link to reject membership request.'),
      'id' => 'reject_membership_request',
    ],
  ];

  return $data;
}
