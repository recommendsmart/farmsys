<?php

namespace Drupal\flow\Plugin\flow\Subject;

use Drupal\flow\Helpers\StackSubjectTrait;
use Drupal\flow\Plugin\FlowSubjectBase;

/**
 * Subject for content that is being deleted.
 *
 * @FlowSubject(
 *   id = "delete",
 *   label = @Translation("Content being deleted"),
 *   task_modes = {"delete"},
 *   deriver = "Drupal\flow\Plugin\flow\Derivative\Subject\DeleteDeriver"
 * )
 */
class Delete extends FlowSubjectBase {

  use StackSubjectTrait;

}
