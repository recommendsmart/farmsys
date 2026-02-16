<?php

namespace Drupal\auditfiles\Batch;

use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\auditfiles\ServiceAuditFilesUsedNotReferenced;

/**
 * Process batch files.
 */
class AuditFilesUsedNotReferencedBatchProcess {

  /**
   * The File entity ID to delete.
   *
   * @var int
   */
  protected $fileId;

  /**
   * ManagedNotUsed service.
   *
   * @var \Drupal\auditfiles\ServiceAuditFilesusedNotReferenced
   */
  protected $usedNotReferenced;

  /**
   * Class constructor.
   *
   * @param \Drupal\auditfiles\ServiceAuditFilesUsedNotReferenced $used_not_referenced
   *   Injected ServiceAuditFilesUsedNotManaged service.
   * @param int $file_id
   *   File entity ID to delete.
   */
  public function __construct(ServiceAuditFilesUsedNotReferenced $used_not_referenced, $file_id) {
    $this->usedNotReferenced = $used_not_referenced;
    $this->fileId = $file_id;
  }

  /**
   * The batch process for process the file ID(s).
   *
   * @param int $file_id
   *   File entity ID to delete.
   * @param array $context
   *   Batch context.
   */
  public static function auditfilesUsedNotReferencedBatchDeleteProcessBatch($file_id, array &$context) {
    $usedNotReferenced = \Drupal::service('auditfiles.used_not_referenced');
    $worker = new static($usedNotReferenced, $file_id);
    $worker->dispatch($context);
  }

  /**
   * Processes file removal from file_usage that are not referenced in content.
   *
   * @param array $context
   *   Batch context.
   */
  protected function dispatch(array &$context) {
    $this->usedNotReferenced->auditfilesUsedNotReferencedBatchDeleteProcessFile($this->fileId);
    $context['results'][] = Html::escape($this->fileId);
    $context['message'] = new TranslatableMarkup('Processed file ID %file_id.', ['%file_id' => $this->fileId]);
  }

}
