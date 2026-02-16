<?php

/**
 * @file
 * Contains \Drupal\resource\Plugin\Action\MarkAsActive.
 */

namespace Drupal\resource\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Marks a resource as active.
 *
 * @Action(
 *   id = "resource_active_action",
 *   label = @Translation("Mark as active"),
 *   type = "resource"
 * )
 */
class MarkAsActive extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->get('active')->setValue(TRUE);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\resource\ResourceInterface $object */
    $result = $object->access('update', $account, TRUE)
      ->andIf($object->get('active')->access('edit', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
