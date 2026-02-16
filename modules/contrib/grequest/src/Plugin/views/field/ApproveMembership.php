<?php

namespace Drupal\grequest\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityLink;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to approve a membership request.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("approve_membership_request")
 */
class ApproveMembership extends EntityLink {

  /**
   * {@inheritdoc}
   */
  protected function getEntityLinkTemplate() {
    return 'group-approve-membership';
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
    return $this->t('Approve membership');
  }

}
