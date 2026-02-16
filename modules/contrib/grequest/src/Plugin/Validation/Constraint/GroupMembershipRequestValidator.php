<?php

namespace Drupal\grequest\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks user membership.
 */
class GroupMembershipRequestValidator extends ConstraintValidator {

  /**
   * Type-hinting in parent Symfony class is off, let's fix that.
   *
   * @var \Symfony\Component\Validator\Context\ExecutionContextInterface
   */
  protected $context;

  /**
   * {@inheritdoc}
   */
  public function validate($group_content, Constraint $constraint) {

    /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
    /** @var \Drupal\grequest\Plugin\Validation\Constraint\GroupRequestMembership $constraint */

    // Apply logic only to group request membership group content.
    if ($group_content->getContentPlugin()->getPluginId() !== 'group_membership_request') {
      return;
    }

    // Only run our checks if a group was referenced.
    if (!$group = $group_content->getGroup()) {
      return;
    }

    // Only run our checks if an entity was referenced.
    if (empty($group_content->getEntity())) {
      return;
    }

    if ($group->getMember($group_content->getEntity())) {
      $this->context->addViolation($constraint->message, [
        '@name' => $group_content->getEntity()->getDisplayName(),
      ]);
    }

  }

}
