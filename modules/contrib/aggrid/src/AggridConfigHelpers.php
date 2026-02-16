<?php

namespace Drupal\aggrid;

use Drupal\aggrid\Entity\Aggrid;

/**
 * Helper functions for agGrid config Entities
 *
 */
class AggridConfigHelpers {

  public function getDefaults($aggrid_id) {
    $aggridDefault = [];

    // Get config for aggrid.
    $config = \Drupal::config('aggrid.general');
    // Set the aggrid setting variable
    $aggridgsjson = json_decode($config->get('aggridgsjson'));
    // Get the global aggrid row settings
    if(isset($aggridgsjson->rowSettings)) {
      $gsRowSettings = json_decode(json_encode($aggridgsjson->rowSettings));
    }

    // Fetch aggrid entity
    $aggridEntity = Aggrid::load($aggrid_id);

    if (!empty($aggridEntity)) {
      $aggridDefault['default'] = json_decode($aggridEntity->get('aggridDefault'));

      $aggridDefault['addOptions'] = @json_decode($aggridEntity->get('addOptions'));

      $aggridRowSettings = @json_decode(json_encode($aggridDefault['default']->rowSettings), true);
      if (isset($gsRowSettings) && isset($aggridRowSettings)) {
        $aggridRowSettings = array_replace_recursive($gsRowSettings, $aggridRowSettings);
      }
      $aggridDefault['aggridRowSettings'] = $aggridRowSettings;
    }

    return $aggridDefault;
  }

  public function getHeaders($columnDefs, $dataHeaderSettings = null) {

    $getHeaders = [];

    $columns = [];
    $headers = [];

    // Build table.
    $rowIndex = 0;
    $colIndex = 0;

    foreach ($columnDefs as $column) {
      $rowIndex = $rowIndex > 1 ? $rowIndex : 1;
      $colIndex++;

      $columns[1][$colIndex][$colIndex] = [];
      $columns[1][$colIndex][$colIndex]['headerName'] = $column->headerName;
      $columns[1][$colIndex][$colIndex]['headerNameFull'] = $column->headerName;
      $columns[1][$colIndex][$colIndex]['field'] = isset($column->field) ? $column->field : NULL;
      $columns[1][$colIndex][$colIndex]['width'] = isset($column->width) ? $column->width : NULL;
      $columns[1][$colIndex][$colIndex]['minWidth'] = isset($column->minWidth) ? $column->minWidth : NULL;
      $columns[1][$colIndex][$colIndex]['viewHide'] = isset($column->viewHide) ? $column->viewHide : false;

      // If children, then dive down for headers, otherwise establish column.
      if (isset($column->children)) {
        $colCount = count($column->children);
        $columns[1][$colIndex][$colIndex]['colspan'] = $colCount;
        $count2 = 0;
        foreach ($column->children as $child) {
          // If column is hidden on view, reduce previous colspan by one
          if (isset($child->viewHide)
            && $child->viewHide) {
            $colCount = $columns[1][$colIndex][$colIndex]['colspan'];
            $columns[1][$colIndex][$colIndex]['colspan'] = $colCount - 1;
          }

          $rowIndex = $rowIndex > 2 ? $rowIndex : 2;
          $count2++;

          $columns[2][$colIndex][$count2] = [];
          $columns[2][$colIndex][$count2]['headerName'] = $child->headerName;
          $columns[2][$colIndex][$count2]['headerNameFull'] = $column->headerName . ' - ' . $child->headerName;
          $columns[2][$colIndex][$count2]['field'] = isset($child->field) ? $child->field : NULL;
          $columns[2][$colIndex][$count2]['width'] = isset($child->width) ? $child->width : NULL;
          $columns[2][$colIndex][$count2]['minWidth'] = isset($child->minWidth) ? $child->minWidth : NULL;
          $columns[2][$colIndex][$count2]['viewHide'] = isset($child->viewHide) ? $child->viewHide : false;

          // if the viewhide is true viewHide as true for this child
          if ($columns[1][$colIndex][$colIndex]['viewHide']) {
            $columns[2][$colIndex][$count2]['viewHide'] = true;
          }

          // Set the field if available.
          if (isset($child->field)) {
            $headers[] = $child->field;
            $columns[0][$child->field] = [];
            $columns[0][$child->field]['viewHide'] = $columns[2][$colIndex][$count2]['viewHide'];
          }

          // Just one for colspan.
          $columns[2][$colIndex][$count2]['colspan'] = 1;
        }

      }
      else {
        // Just one for colspan.
        $columns[1][$colIndex][$colIndex]['colspan'] = 1;
      }

      // If the parent column colspan is now 0, then default viewHide to true (all children are hidden)
      if ($columns[1][$colIndex][$colIndex]['colspan'] <= 0) {
        $columns[1][$colIndex][$colIndex]['viewHide'] = true;
      }

      // If no children, set the field if available.
      if (!isset($column->children) && isset($column->field)) {
        $headers[] = $column->field;
        $columns[0][$column->field] = [];
        $columns[0][$column->field]['viewHide'] = $columns[1][$colIndex][$colIndex]['viewHide'];
      }
    }

    // Set return
    $getHeaders['rowIndex'] = $rowIndex;
    $getHeaders['colIndex'] = $colIndex;
    $getHeaders['headers'] = $headers;
    $getHeaders['columns'] = $columns;

    return $getHeaders;
  }

  public function getRowSettings($aggridRowSettings, $headers, $rowData, $rowPrefix) {
    // Set global complimentary switch
    // Set the rest of the row settings
    $rowSettings[][] = '';
    if (is_array($rowData)) {
      for ($i = 0; $i < count($rowData); $i++) {
        foreach ($headers as $field) {
          $rowSettings[$i][$field] = [];
          if (isset($aggridRowSettings[$rowPrefix . $i][$field])) {
            $rowSettings[$i][$field] = $rowSettings[$i][$field] + $aggridRowSettings[$rowPrefix . $i][$field];
          }
          if (isset($aggridRowSettings[$rowPrefix . $i]['rowDefault'])) {
            $rowSettings[$i][$field] = $rowSettings[$i][$field] + $aggridRowSettings[$rowPrefix . $i]['rowDefault'];
          }
          if (isset($aggridRowSettings[$rowPrefix . 'default'][$field])) {
            $rowSettings[$i][$field] = $rowSettings[$i][$field] + $aggridRowSettings[$rowPrefix . 'default'][$field];
          }
          if (isset($aggridRowSettings[$rowPrefix . 'default']['rowDefault'])) {
            $rowSettings[$i][$field] = $rowSettings[$i][$field] + $aggridRowSettings[$rowPrefix . 'default']['rowDefault'];
          }
        }
      }
    }

    return $rowSettings;
  }

}
