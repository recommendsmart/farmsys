<?php

namespace Drupal\flow\Plugin\flow\Subject;

use Drupal\flow\Helpers\StackSubjectTrait;
use Drupal\flow\Plugin\FlowSubjectBase;

/**
 * Subject for content that is being saved.
 *
 * @FlowSubject(
 *   id = "save",
 *   label = @Translation("Content being saved"),
 *   task_modes = {"save"},
 *   deriver = "Drupal\flow\Plugin\flow\Derivative\Subject\SaveDeriver"
 * )
 */
class Save extends FlowSubjectBase {

  use StackSubjectTrait;

}
