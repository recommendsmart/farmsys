<?php

namespace Drupal\flow\Plugin\flow\Subject;

use Drupal\flow\Plugin\FlowSubjectBase;

/**
 * Subject for content that is passed to an action.
 *
 * @FlowSubject(
 *   id = "action",
 *   label = @Translation("Action on content"),
 *   deriver = "Drupal\flow\Plugin\flow\Derivative\Subject\ActionDeriver"
 * )
 */
class Action extends FlowSubjectBase {

  /**
   * The items of content that is passed to an action.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface[]
   */
  public static array $items = [];

  /**
   * {@inheritdoc}
   */
  public function getSubjectItems(): iterable {
    return self::$items;
  }

}
