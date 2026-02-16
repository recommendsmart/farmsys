<?php

namespace Drupal\auditfiles\Batch;

use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\auditfiles\ServiceAuditFilesNotOnServer;

/**
 * Process batch files.
 */
class AuditFilesNotOnServerBatchProcess {

  /**
   * The File entity ID to process.
   *
   * @var int
   */
  protected $fileId;

  /**
   * NotOnServer service.
   *
   * @var \Drupal\auditfiles\ServiceAuditFilesNotOnServer
   */
  protected $notOnServer;

  /**
   * Class constructor.
   *
   * @param \Drupal\auditfiles\ServiceAuditFilesNotOnServer $not_on_server
   *   Injected ServiceAuditFilesNotOnServer service.
   * @param int $file_id
   *   File entity ID to delete.
   */
  public function __construct(ServiceAuditFilesNotOnServer $not_on_server, $file_id) {
    $this->notOnServer = $not_on_server;
    $this->fileId = $file_id;
  }

  /**
   * The batch process for deleting the file.
   *
   * Used by the Batch API to keep track of and pass data from one operation to
   * the next.
   *
   * @param int $file_id
   *   File entity ID to delete.
   * @param array $context
   *   Batch context.
   */
  public static function auditfilesNotOnServerBatchDeleteProcessBatch($file_id, array &$context) {
    $notOnServer = \Drupal::service('auditfiles.not_on_server');
    $worker = new static($notOnServer, $file_id);
    $worker->dispatch($context);
  }

  /**
   * Processes file removal from file_usage that are not referenced in content.
   *
   * @param array $context
   *   Batch context.
   */
  protected function dispatch(array &$context) {
    $this->notOnServer->auditfilesNotOnServerBatchDeleteProcessFile($this->fileId);
    $context['results'][] = Html::escape($this->fileId);
    $context['message'] = new TranslatableMarkup('Processed file ID %file_id.', ['%file_id' => $this->fileId]);
  }

}
