<?php

namespace Drupal\auditfiles\Batch;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Process batch process completion messages.
 */
class AuditFilesBatchProcess {

  /**
   * Messenger Factor Service called when the batch is completed.
   *
   * @todo Refactor into a factory worker process class.
   */
  public static function finishBatch($success, $results, $operations) {
    $messenger = \Drupal::service('messenger');
    if (!$success) {
      $error_operation = reset($operations);
      $messenger->addError(
        new TranslatableMarkup('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

}
