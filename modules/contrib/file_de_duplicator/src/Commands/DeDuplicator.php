<?php

namespace Drupal\file_de_duplicator\Commands;

use Drush\Commands\DrushCommands;
use Drupal\file_de_duplicator\DuplicateFinder;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://git.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://git.drupalcode.org/devel/tree/drush.services.yml
 */
class DeDuplicator extends DrushCommands {

  /**
   * The entity type manager.
   *
   * @var \Drupal\file_de_duplicator\DuplicateFinder
   */
  protected $duplicateFinder;

  /**
   * DeDuplicator constructor.
   *
   * @param \Drupal\file_de_duplicator\DuplicateFinder $duplicate_finder
   *   Duplicate finder service.
   */
  public function __construct(DuplicateFinder $duplicate_finder) {
    parent::__construct();
    $this->duplicateFinder = $duplicate_finder;
  }

  /**
   * Find duplicates
   * 
   * @command file-de-duplicator:find-duplicates
   */
  public function findDuplicates() {
    $next_id = 1;
    do {
      $current_id = $next_id;
      $this->logger()->notice(dt('Finding from @id...', ['@id' => $current_id]));
      $next_id = $this->duplicateFinder->find($current_id, 1);
    } while ($next_id != $current_id);
    
  }

  /**
   * Replace duplicates. Make sure to run file-de-duplicator:find-duplicates to find duplicates.
   * 
   * @command file-de-duplicator:replace-duplicates
   */
  public function replaceDuplicates() {
    $database = \Drupal::database();
    do {
      $duplicate_record = $database->select('duplicate_files', 'd')
        ->fields('d', ['fid', 'original_fid'])
        ->isNull('d.replaced_timestamp')
        ->range(0, 1)
        ->execute()->fetchObject();
      if ($duplicate_record) {
        $this->logger()->notice(dt('Replacing duplicate file @duplicate with original @original...', ['@duplicate' => $duplicate_record->fid, '@original' => $duplicate_record->original_fid]));
        \Drupal::service('file_de_duplicator.duplicate_finder')->replace($duplicate_record->fid, $duplicate_record->original_fid);
      }
      else {
        break;
      }
    } while(TRUE);
  }

  /**
   * Clear all infomation about duplicates found.
   * 
   * @command file-de-duplicator:clear
   */
  public function clear() {
    $this->duplicateFinder->clearFindings();
  }
}
