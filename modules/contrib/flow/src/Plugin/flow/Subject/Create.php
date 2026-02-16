<?php

namespace Drupal\flow\Plugin\flow\Subject;

use Drupal\flow\Helpers\StackSubjectTrait;
use Drupal\flow\Plugin\FlowSubjectBase;

/**
 * Subject for content that is being created.
 *
 * @FlowSubject(
 *   id = "create",
 *   label = @Translation("Content being created"),
 *   task_modes = {"create"},
 *   deriver = "Drupal\flow\Plugin\flow\Derivative\Subject\CreateDeriver"
 * )
 */
class Create extends FlowSubjectBase {

  use StackSubjectTrait;

}
