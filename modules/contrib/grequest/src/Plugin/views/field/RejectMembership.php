<?php

namespace Drupal\grequest\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityLink;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to reject a membership request.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("reject_membership_request")
 */
class RejectMembership extends EntityLink {

  /**
   * {@inheritdoc}
   */
  protected function getEntityLinkTemplate() {
    return 'group-reject-membership';
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row) {
    $this->options['alter']['query'] = $this->getDestinationArray();
    return parent::renderLink($row);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Reject membership');
  }

}
