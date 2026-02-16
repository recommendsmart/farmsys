<?php

namespace Drupal\flow\Helpers;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\flow\Flow;
use Drupal\flow\Plugin\FlowSubjectInterface;

/**
 * A trait for tasks that perform a single operation on a subject item.
 */
trait SingleTaskOperationTrait {

  /**
   * {@inheritdoc}
   */
  public function operate(FlowSubjectInterface $subject): void {
    foreach ($subject->getSubjectItems() as $item) {
      $this->doOperate($item);
    }
  }

  /**
   * Operates the task on the given subject item.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The subject item, that is always a content entity.
   */
  protected function doOperate(ContentEntityInterface $entity): void {
    // When you know that you've done changes on the entity, inform Flow
    // about this, by telling that it needs to be saved.
    Flow::needsSave($entity, $this);
  }

}
