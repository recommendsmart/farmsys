<?php

namespace Drupal\auditfiles\Batch;

use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\auditfiles\ServiceAuditFilesNotInDatabase;

/**
 * Process batch files.
 */
class AuditFilesNotInDatabaseBatchProcess {

  /**
   * The file name to process.
   *
   * @var string
   */
  protected $fileName;

  /**
   * ReferencedNotUsed service.
   *
   * @var \Drupal\auditfiles\ServiceAuditFilesNotInDatabase
   */
  protected $notInDatabase;

  /**
   * Class constructor.
   *
   * @param \Drupal\auditfiles\ServiceAuditFilesNotInDatabase $not_in_database
   *   Injected ServiceAuditFilesUsedNotManaged service.
   * @param string $file_name
   *   File name to process.
   */
  public function __construct(ServiceAuditFilesNotInDatabase $not_in_database, $file_name) {
    $this->notInDatabase = $not_in_database;
    $this->fileName = $file_name;
  }

  /**
   * The batch process for adding the file.
   *
   * @param string $filename
   *   File name that to be process.
   * @param array $context
   *   Batch context.
   */
  public static function auditfilesNotInDatabaseBatchAddProcessBatch($filename, array &$context) {
    $notInDatabase = \Drupal::service('auditfiles.not_in_database');
    $worker = new static($notInDatabase, $filename);
    $worker->addDispatch($context);
  }

  /**
   * Adds filenames referenced in content in file_managed but not in file_usage.
   *
   * @param array $context
   *   Batch context.
   */
  protected function addDispatch(array &$context) {
    $this->notInDatabase->auditfilesNotInDatabaseBatchAddProcessFile($this->fileName);
    $context['results'][] = Html::escape($this->fileName);
    $context['message'] = new TranslatableMarkup('Processed %filename.', ['%filename' => $this->fileName]);
  }

  /**
   * The batch process for deleting the file.
   *
   * @param string $filename
   *   File name that needs to be processed.
   * @param array $context
   *   Batch context.
   */
  public static function auditfilesNotInDatabaseBatchDeleteProcessBatch($filename, array &$context) {
    $notInDatabase = \Drupal::service('auditfiles.not_in_database');
    $worker = new static($notInDatabase, $filename);
    $worker->deleteDispatch($context);
  }

  /**
   * Deletes filenames referenced in content frm file_managed not in file_usage.
   *
   * @param array $context
   *   Batch context.
   */
  protected function deleteDispatch(array &$context) {
    $this->notInDatabase->auditfilesNotInDatabaseBatchDeleteProcessFile($this->fileName);
    $context['results'][] = Html::escape($this->fileName);
    $context['message'] = new TranslatableMarkup('Processed %filename.', ['%filename' => $this->fileName]);
  }

}
