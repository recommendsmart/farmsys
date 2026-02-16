<?php

namespace Drupal\aggrid\Commands;

use Drush\Commands\DrushCommands;
use Exception;


/**
 * Class AggridDrushCommands.
 *
 * @package Drupal\aggrid\Commands
 */
class AggridDrushCommands extends DrushCommands {

  /**
   * Downloads the most current ag-Grid Library files.
   *
   *   Argument provided to the drush command.
   *
   * @command aggrid:download
   * @aliases agg-download
   * @usage aggrid:download
   *   Download/Update the ag-Grid Library from GitHub
   */
  public function download() {
    // Create a file system service manager.
    // Remove the existing directory if it exists.
    $library_directory = DRUPAL_ROOT . '/libraries/';
    $clear_cache = false;
    try {
  
      $aggrid_library_directory = $library_directory . '/ag-grid';
      if (file_exists($library_directory) && file_exists($aggrid_library_directory)) {
        // Remove the existing file.
        if (file_exists($aggrid_library_directory . '/ag-grid-community.min.noStyle.js')) {
          unlink($aggrid_library_directory . '/ag-grid-community.min.noStyle.js');
        }
        if (file_exists($aggrid_library_directory . '/ag-grid-enterprise.min.noStyle.js')) {
          unlink($aggrid_library_directory . '/ag-grid-enterprise.min.noStyle.js');
        }
        if (file_exists($aggrid_library_directory . '/css/ag-grid.css')) {
          unlink($aggrid_library_directory . '/css/ag-grid.css');
        }
        if (file_exists($aggrid_library_directory . '/css/ag-theme-balham.css')) {
          unlink($aggrid_library_directory . '/css/ag-theme-balham.css');
        }
        rmdir($aggrid_library_directory .'/css');
        rmdir($aggrid_library_directory);
      }
  
      // Create the directory(s).
      if (!file_exists($library_directory)) {
        mkdir(DRUPAL_ROOT . '/libraries');
      }
      mkdir(DRUPAL_ROOT . '/libraries/ag-grid');
      mkdir(DRUPAL_ROOT . '/libraries/ag-grid/css');
  
      // Download the community file.
      if (!drush_shell_exec('curl -o ' . DRUPAL_ROOT . '/libraries/ag-grid' . '/ag-grid-community.min.noStyle.js https://github.com/ag-grid/ag-grid/raw/master/packages/ag-grid-community/dist/ag-grid-community.min.noStyle.js')){
        throw new Exception('Community Edition download failed - [stopping]');
      }
      drush_log(dt('ag-Grid Community library has been successfully installed at libraries/ag-grid', [], 'success'), 'success');
      
      // Download the enterprise file.
      if (!drush_shell_exec('curl -o ' . DRUPAL_ROOT . '/libraries/ag-grid' . '/ag-grid-enterprise.min.noStyle.js https://github.com/ag-grid/ag-grid/raw/master/packages/ag-grid-enterprise/dist/ag-grid-enterprise.min.noStyle.js')){
        throw new Exception('Enterprise Edition download failed - [stopping]');
      }
      drush_log(dt('ag-Grid Enterprise library has been successfully installed at libraries/ag-grid', [], 'success'), 'success');
  
      if (!drush_shell_exec('curl -o ' . DRUPAL_ROOT . '/libraries/ag-grid/css' . '/ag-grid.css https://raw.githubusercontent.com/ag-grid/ag-grid/master/packages/ag-grid-community/dist/styles/ag-grid.css')){
        throw new Exception('ag-Grid CSS download failed - [stopping]');
      }
      drush_log(dt('ag-Grid CSS has been successfully installed at libraries/ag-grid/css', [], 'success'), 'success');
      
      // Get ag-Grid CSS
      if (!drush_shell_exec('curl -o ' . DRUPAL_ROOT . '/libraries/ag-grid/css' . '/ag-theme-balham.css https://raw.githubusercontent.com/ag-grid/ag-grid/master/packages/ag-grid-community/dist/styles/ag-theme-balham.css')){
        throw new Exception('Balham (default) theme download failed - [stopping]');
      }
      drush_log(dt('ag-Grid theme Balham (default) CSS has been successfully installed at libraries/ag-grid/css', [], 'success'), 'success');
      
      drupal_flush_all_caches();
  
    } catch (Exception $e) {
      drush_log(dt('aggrid Download Error: @error', ['@error' => $e->getMessage()], 'error'), 'error');
    }
  }

}
