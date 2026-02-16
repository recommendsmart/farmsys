<?php

namespace Drupal\node_singles\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\node_singles\Service\NodeSinglesInterface;

/**
 * Creates a single node after node type changes, if necessary.
 */
class NodeTypeUpdateEventSubscriber {

  /**
   * The node singles service.
   *
   * @var \Drupal\node_singles\Service\NodeSinglesInterface
   */
  private $singles;

  /**
   * Constructs the event subscriber.
   *
   * @param \Drupal\node_singles\Service\NodeSinglesInterface $singles
   *   The node singles service.
   */
  public function __construct(NodeSinglesInterface $singles) {
    $this->singles = $singles;
  }

  /**
   * Creates a single node after node type changes, if necessary.
   */
  public function checkForSingles(EntityInterface $entity): void {
    if ($entity instanceof NodeTypeInterface) {
      $this->singles->checkSingle($entity);
    }
  }

}
