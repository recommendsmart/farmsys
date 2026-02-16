<?php

namespace Drupal\auditfiles\Batch;

use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\auditfiles\ServiceAuditFilesReferencedNotUsed;

/**
 * Process batch files.
 */
class AuditFilesReferencedNotUsedBatchProcess {

  /**
   * The entity reference ID to delete.
   *
   * @var int
   */
  protected $referenceId;

  /**
   * ReferencedNotUsed service.
   *
   * @var \Drupal\auditfiles\ServiceAuditFilesReferencedNotUsed
   */
  protected $referencedNotUsed;

  /**
   * Class constructor.
   *
   * @param \Drupal\auditfiles\ServiceAuditFilesReferencedNotUsed $referenced_not_used
   *   Injected ServiceAuditFilesUsedNotManaged service.
   * @param int $reference_id
   *   File entity ID to delete.
   */
  public function __construct(ServiceAuditFilesReferencedNotUsed $referenced_not_used, $reference_id) {
    $this->referencedNotUsed = $referenced_not_used;
    $this->referenceId = $reference_id;
  }

  /**
   * Batch Process for Adding a file reference.
   *
   * @param int $reference_id
   *   File entity reference ID to add.
   * @param array $context
   *   Batch context.
   */
  public static function auditfilesReferencedNotUsedBatchAddProcessBatch($reference_id, array &$context) {
    $referencedNotUsed = \Drupal::service('auditfiles.referenced_not_used');
    $worker = new static($referencedNotUsed, $reference_id);
    $worker->addDispatch($context);
  }

  /**
   * Processes entity reference additions from content entities to file_managed.
   *
   * @param array $context
   *   Batch context.
   */
  protected function addDispatch(array &$context) {
    $this->referencedNotUsed->auditfilesReferencedNotUsedBatchAddProcessFile($this->referenceId);
    $context['results'][] = Html::escape($this->referenceId);
    $context['message'] = new TranslatableMarkup('Processed file ID %file_id.', ['%file_id' => $this->referenceId]);
  }

  /**
   * Batch Process for Deleting a file reference.
   *
   * @param int $reference_id
   *   File entity reference ID to delete.
   * @param array $context
   *   Batch context.
   */
  public static function auditfilesReferencedNotUsedBatchDeleteProcessBatch($reference_id, array &$context) {
    $referencedNotUsed = \Drupal::service('auditfiles.referenced_not_used');
    $worker = new static($referencedNotUsed, $reference_id);
    $worker->deleteDispatch($context);
  }

  /**
   * Processes entity reference deletions from content entities to file_managed.
   *
   * @param array $context
   *   Batch context.
   */
  protected function deleteDispatch(array &$context) {
    $this->referencedNotUsed->auditfilesReferencedNotUsedBatchDeleteProcessFile($this->referenceId);
    $context['results'][] = Html::escape($this->referenceId);
    $context['message'] = new TranslatableMarkup('Processed file ID %file_id.', ['%file_id' => $this->referenceId]);
  }

}
