<?php

namespace Drupal\aggrid;

use Drupal\aggrid\AggridConfigHelpers;

/**
 * Items for applying aggrid suppression to data
 * The suppression is used to block view of certain numbers if they
 * fall below a threshold.
 *
 */
class AggridSuppression {

  /**
   * Complimentary suppression for aggrid item.
   *
   * @var array
   */
  protected $rowSuppression;

  /**
   * @param $rowSettings
   * @param $headers
   * @param $rowData
   * @param $suppOverride
   * @param $suppComplimentary
   * @return mixed
   */
  public function processSuppression($rowSettings, $headers, $rowData, $suppOverride = false, $suppComplimentary = false, $suppCharacterOverride = NULL) {
    // Variable for return data
    $processSuppression = [];

    // Suppression variables
    $suppCell = [];
    $suppCellEligible = [];
    $suppRowCount = [];
    $suppColCount = [];
    // Check for suppression groups and mark accordingly.
    $suppRowOther = [];
    $suppColOther = [];

    // Run Level 1 basic suppression
    $suppArray = $this->processSuppression_Basic($rowSettings, $headers, $rowData, $suppCell, $suppCellEligible, $suppRowCount, $suppColCount, $suppRowOther, $suppColOther);
    // Set the variables
    $suppCell = $suppArray['suppCell'];
    $suppCellEligible = $suppArray['suppCellEligible'];
    $suppRowCount = $suppArray['suppRowCount'];
    $suppColCount = $suppArray['suppColCount'];
    $suppRowOther = $suppArray['suppRowOther'];
    $suppColOther = $suppArray['suppColOther'];

    /*
     * Level 2 complimentary suppression
     *
     * If enabled, this will attempt complimentary suppression to not allow just 1 suppression
     * on a row/column.
     *
     */
    // If complimentary is enabled... do it.
    if ($suppComplimentary) {
      // Do this 4x
      for ($i = 0; $i < 4; $i++) {
        // Find rows with a single item
        // Single Rows
        $suppSingleRow = [];
        foreach($suppRowCount as $row => $count) {
          if ($count == 1) {
            array_push($suppSingleRow, $row);
          }
        }
        // Single Columns
        $suppSingleCol = [];
        foreach($suppColCount as $column => $count) {
          if ($count == 1) {
            array_push($suppSingleCol, $column);
          }
        }

        /**
         *
         * Row-by-row (top to bottom) Complimentary Suppression checking across columns on each row (from left to right)
         *
         */
        // Loop through rows that need further suppression
        foreach($suppSingleRow as $numRow => $row) {
          $rowSuppressed = 0;
          // Check for eligible single row/col to suppress first (remove two at once)
          foreach($suppSingleCol as $numCol => $col) {
            // Check if cell allows column complimentary suppression across the columns
            if (isset($rowSettings[$row][$col]['valueSuppression']['complimentary'])
              && $rowSettings[$row][$col]['valueSuppression']['complimentary']
              && $rowSettings[$row][$col]['valueSuppression']['complimentary'] !== "row") {
              $rowSuppAllowed = true;
            }
            else {
              $rowSuppAllowed = false;
            }
            // Check for complimentary row settings. If there is a list set and row is not in list, then not allowed.
            if (isset($rowSettings[$row])
              && isset($rowSettings[$row][$col])
              && isset($rowSettings[$row][$col]['valueSuppression'])
              && isset($rowSettings[$row][$col]['valueSuppression']['complimentary_column'])) {
              if (!in_array($row, $rowSettings[$row][$col]['valueSuppression']['complimentary_column'])) {
                $rowSuppAllowed = false;
              }
            }
            // See if we can complimentary suppress
            if (!$rowSuppressed && $rowSuppAllowed && isset($suppCellEligible[$row]) && in_array($col, $suppCellEligible[$row])) {
              $suppCell[$row][] = $col;
              // Remove single row/col from list
              unset($suppSingleRow[$numRow]);
              unset($suppSingleCol[$numCol]);
              // Add to counts
              $suppRowCount[$row]++;
              // Dont add if suppression just happens across the columns
              if ($rowSettings[$row][$col]['valueSuppression']['complimentary'] !== "column") {
                $suppColCount[$col]++;
              }
              // Mark row as suppressed
              $rowSuppressed = 1;
              // Re-run Level 1 basic suppression
              $suppArray = $this->processSuppression_Basic($rowSettings, $headers, $rowData, $suppCell, $suppCellEligible, $suppRowCount, $suppColCount, $suppRowOther, $suppColOther);
              // Set the variables
              $suppCell = $suppArray['suppCell'];
              $suppCellEligible = $suppArray['suppCellEligible'];
              $suppRowCount = $suppArray['suppRowCount'];
              $suppColCount = $suppArray['suppColCount'];
              $suppRowOther = $suppArray['suppRowOther'];
              $suppColOther = $suppArray['suppColOther'];
            }
          }
          // Attempt to suppress a n eligible column with at least more than 1 suppression already in place.
          if (!$rowSuppressed) {
            foreach($suppColCount as $col => $count){
              // Check if cell allows row complimentary suppression across the columns
              if (isset($rowSettings[$row][$col]['valueSuppression']['complimentary'])
                && ($rowSettings[$row][$col]['valueSuppression']['complimentary']
                  && $rowSettings[$row][$col]['valueSuppression']['complimentary'] !== "row")) {
                $rowSuppAllowed = true;
              }
              else {
                $rowSuppAllowed = false;
              }
              // Check for complimentary row settings. If there is a list set and row is not in list, then not allowed.
              if (isset($rowSettings[$row])
                && isset($rowSettings[$row][$col])
                && isset($rowSettings[$row][$col]['valueSuppression'])
                && isset($rowSettings[$row][$col]['valueSuppression']['complimentary_column'])) {
                if (!in_array($row, $rowSettings[$row][$col]['valueSuppression']['complimentary_column'])) {
                  $rowSuppAllowed = false;
                }
              }
              // See if we can complimentary suppress
              if (!$rowSuppressed && $rowSuppAllowed && $count > 1 && isset($suppCellEligible[$row]) && in_array($col, $suppCellEligible[$row])) {
                $suppCell[$row][] = $col;
                // Remove column from eligibility
                if(($key = array_search($col, $suppCellEligible[$row])) !== false) {
                  unset($suppCellEligible[$row][$key]);
                }
                // Remove single row/col from list
                unset($suppSingleRow[$numRow]);
                if(($key = array_search($col, $suppSingleCol)) !== false) {
                  unset($suppSingleCol[$key]);
                }
                // Add to counts
                $suppRowCount[$row]++;
                // Dont add if suppression just happens across the columns
                if ($rowSettings[$row][$col]['valueSuppression']['complimentary'] !== "column") {
                  $suppColCount[$col]++;
                }
                // Mark row as suppressed
                $rowSuppressed = 1;
                // Re-run Level 1 basic suppression
                $suppArray = $this->processSuppression_Basic($rowSettings, $headers, $rowData, $suppCell, $suppCellEligible, $suppRowCount, $suppColCount, $suppRowOther, $suppColOther);
                // Set the variables
                $suppCell = $suppArray['suppCell'];
                $suppCellEligible = $suppArray['suppCellEligible'];
                $suppRowCount = $suppArray['suppRowCount'];
                $suppColCount = $suppArray['suppColCount'];
                $suppRowOther = $suppArray['suppRowOther'];
                $suppColOther = $suppArray['suppColOther'];
              }
            }
          }
          // Attempt to suppress an eligible column with the least value
          if (!$rowSuppressed) {
            // Get totals for each column in a row
            $suppColTotal_this = [];
            foreach($headers as $col) {
              if (isset($rowData[$row]->$col) && is_numeric($rowData[$row]->$col)) {
                $suppColTotal_this[$col] = $rowData[$row]->$col;
              }
            }
            // Order the items
            asort($suppColTotal_this);
            // Do the loop
            foreach($suppColTotal_this as $col => $value){
              // Check if cell allows row complimentary suppression across the columns
              if (isset($rowSettings[$row][$col]['valueSuppression']['complimentary'])
                && ($rowSettings[$row][$col]['valueSuppression']['complimentary']
                  && $rowSettings[$row][$col]['valueSuppression']['complimentary'] !== "row")) {
                $rowSuppAllowed = true;
              }
              else {
                $rowSuppAllowed = false;
              }
              // Check for complimentary row settings. If there is a list set and row is not in list, then not allowed.
              if (isset($rowSettings[$row])
                && isset($rowSettings[$row][$col])
                && isset($rowSettings[$row][$col]['valueSuppression'])
                && isset($rowSettings[$row][$col]['valueSuppression']['complimentary_column'])) {
                if (!in_array($row, $rowSettings[$row][$col]['valueSuppression']['complimentary_column'])) {
                  $rowSuppAllowed = false;
                }
              }
              // See if we can complimentary suppress
              if (!$rowSuppressed && $rowSuppAllowed && isset($suppCellEligible[$row]) && in_array($col, $suppCellEligible[$row])
                && $value > 0) {
                $suppCell[$row][] = $col;
                // Remove column from eligibility
                if(($key = array_search($col, $suppCellEligible[$row])) !== false) {
                  unset($suppCellEligible[$row][$key]);
                }
                // Remove single row/col from list
                unset($suppSingleRow[$numRow]);
                if(($key = array_search($col, $suppSingleCol)) !== false) {
                  unset($suppSingleCol[$key]);
                }
                // Add to counts
                $suppRowCount[$row]++;
                // Dont add if suppression just happens across the columns
                if ($rowSettings[$row][$col]['valueSuppression']['complimentary'] !== "column") {
                  $suppColCount[$col]++;
                }
                // Mark row as suppressed
                $rowSuppressed = 1;

                // Re-run Level 1 basic suppression
                $suppArray = $this->processSuppression_Basic($rowSettings, $headers, $rowData, $suppCell, $suppCellEligible, $suppRowCount, $suppColCount, $suppRowOther, $suppColOther);
                // Set the variables
                $suppCell = $suppArray['suppCell'];
                $suppCellEligible = $suppArray['suppCellEligible'];
                $suppRowCount = $suppArray['suppRowCount'];
                $suppColCount = $suppArray['suppColCount'];
                $suppRowOther = $suppArray['suppRowOther'];
                $suppColOther = $suppArray['suppColOther'];
              }
            }
          }
          // Attempt to suppress an eligible column with any value
          if (!$rowSuppressed) {
            foreach($headers as $col){
              // Check if cell allows row complimentary suppression across the columns
              if (isset($rowSettings[$row][$col]['valueSuppression']['complimentary'])
                && ($rowSettings[$row][$col]['valueSuppression']['complimentary']
                  && $rowSettings[$row][$col]['valueSuppression']['complimentary'] !== "row")) {
                $rowSuppAllowed = true;
              }
              else {
                $rowSuppAllowed = false;
              }
              // Check for complimentary row settings. If there is a list set and row is not in list, then not allowed.
              if (isset($rowSettings[$row])
                && isset($rowSettings[$row][$col])
                && isset($rowSettings[$row][$col]['valueSuppression'])
                && isset($rowSettings[$row][$col]['valueSuppression']['complimentary_column'])) {
                if (!in_array($row, $rowSettings[$row][$col]['valueSuppression']['complimentary_column'])) {
                  $rowSuppAllowed = false;
                }
              }
              // See if we can complimentary suppress
              if (!$rowSuppressed && $rowSuppAllowed
                && isset($suppCellEligible[$row]) && in_array($col, $suppCellEligible[$row])
                && $rowData[$row]->$col > 0) {
                $suppCell[$row][] = $col;
                // Remove column from eligibility
                if(($key = array_search($col, $suppCellEligible[$row])) !== false) {
                  unset($suppCellEligible[$row][$key]);
                }
                // Remove single row/col from list
                unset($suppSingleRow[$numRow]);
                if(($key = array_search($col, $suppSingleCol)) !== false) {
                  unset($suppSingleCol[$key]);
                }
                // Add to counts
                $suppRowCount[$row]++;
                // Dont add if suppression just happens across the columns
                if ($rowSettings[$row][$col]['valueSuppression']['complimentary'] !== "column") {
                  $suppColCount[$col]++;
                }
                // Mark row as suppressed
                $rowSuppressed = 1;
                // Re-run Level 1 basic suppression
                $suppArray = $this->processSuppression_Basic($rowSettings, $headers, $rowData, $suppCell, $suppCellEligible, $suppRowCount, $suppColCount, $suppRowOther, $suppColOther);
                // Set the variables
                $suppCell = $suppArray['suppCell'];
                $suppCellEligible = $suppArray['suppCellEligible'];
                $suppRowCount = $suppArray['suppRowCount'];
                $suppColCount = $suppArray['suppColCount'];
                $suppRowOther = $suppArray['suppRowOther'];
                $suppColOther = $suppArray['suppColOther'];
              }
            }
          }
          // Attempt to suppress an eligible column **Catchall
          if (!$rowSuppressed) {
            foreach($headers as $col){
              // Check if cell allows row complimentary suppression across the columns
              if (isset($rowSettings[$row][$col]['valueSuppression']['complimentary'])
                && ($rowSettings[$row][$col]['valueSuppression']['complimentary']
                  && $rowSettings[$row][$col]['valueSuppression']['complimentary'] !== "row")) {
                $rowSuppAllowed = true;
              }
              else {
                $rowSuppAllowed = false;
              }
              // Check for complimentary row settings. If there is a list set and row is not in list, then not allowed.
              if (isset($rowSettings[$row])
                && isset($rowSettings[$row][$col])
                && isset($rowSettings[$row][$col]['valueSuppression'])
                && isset($rowSettings[$row][$col]['valueSuppression']['complimentary_column'])) {
                if (!in_array($row, $rowSettings[$row][$col]['valueSuppression']['complimentary_column'])) {
                  $rowSuppAllowed = false;
                }
              }
              // See if we can complimentary suppress
              if (!$rowSuppressed && $rowSuppAllowed && isset($suppCellEligible[$row]) && in_array($col, $suppCellEligible[$row])) {
                $suppCell[$row][] = $col;
                // Remove column from eligibility
                if(($key = array_search($col, $suppCellEligible[$row])) !== false) {
                  unset($suppCellEligible[$row][$key]);
                }
                // Remove single row/col from list
                unset($suppSingleRow[$numRow]);
                if(($key = array_search($col, $suppSingleCol)) !== false) {
                  unset($suppSingleCol[$key]);
                }
                // Add to counts
                $suppRowCount[$row]++;
                // Dont add if suppression just happens across the columns
                if ($rowSettings[$row][$col]['valueSuppression']['complimentary'] !== "column") {
                  $suppColCount[$col]++;
                }
                // Mark row as suppressed
                $rowSuppressed = 1;
                // Re-run Level 1 basic suppression
                $suppArray = $this->processSuppression_Basic($rowSettings, $headers, $rowData, $suppCell, $suppCellEligible, $suppRowCount, $suppColCount, $suppRowOther, $suppColOther);
                // Set the variables
                $suppCell = $suppArray['suppCell'];
                $suppCellEligible = $suppArray['suppCellEligible'];
                $suppRowCount = $suppArray['suppRowCount'];
                $suppColCount = $suppArray['suppColCount'];
                $suppRowOther = $suppArray['suppRowOther'];
                $suppColOther = $suppArray['suppColOther'];
              }
            }
          }
        }

        /**
         *
         * Column-by-column (left to right) Complimentary Suppression checking down column for row (from top to bottom)
         *
         */
        // Loop through columns that need further suppression
        $colSuppressed = 0;
        foreach($headers as $col) {
          // Attempt to suppress an eligible column in a complimentary suppression group with 1 suppression in place.
          if (isset($suppRowOther[$col])) {
            $suppRowCount_this = [];
            $suppRowTotal_this = [];
            $suppRowGroups = $this->processSuppression_CountGroups($rowData, $suppCell, $col, $suppRowOther);
            // Set variables from function
            $suppRowCount_this = $suppRowGroups['suppRowCount_this'];
            $suppRowTotal_this = $suppRowGroups['suppRowTotal_this'];
            // Loop again, but this time complimentary suppress allowed items that need it in each group.
            foreach($suppRowCount_this as $otherRows_str => $count) {
              // Order the items
              $sorted_suppRowTotal_this = $suppRowTotal_this[$otherRows_str];
              // Sort it
              asort($sorted_suppRowTotal_this);
              // Do the loop
              foreach($sorted_suppRowTotal_this as $row => $total){
                if (isset($suppRowCount_this[$otherRows_str]) && $suppRowCount_this[$otherRows_str] == 1) {
                  // Check if cell allows row complimentary suppression down the row for the column
                  if (isset($rowSettings[$row][$col]['valueSuppression']['complimentary'])
                    && ($rowSettings[$row][$col]['valueSuppression']['complimentary']
                      && $rowSettings[$row][$col]['valueSuppression']['complimentary'] !== "column")) {
                    $colSuppAllowed = true;
                  }
                  else {
                    $colSuppAllowed = false;
                  }
                  // Check for complimentary row settings. If there is a list set and row is not in list, then not allowed.
                  if (isset($rowSettings[$row])
                    && isset($rowSettings[$row][$col])
                    && isset($rowSettings[$row][$col]['valueSuppression'])
                    && isset($rowSettings[$row][$col]['valueSuppression']['complimentary_row'])) {
                    if (!in_array($row, $rowSettings[$row][$col]['valueSuppression']['complimentary_row'])) {
                      $colSuppAllowed = false;
                    }
                  }
                  // See if we can complimentary suppress
                  if (!$colSuppressed && $colSuppAllowed && isset($suppCellEligible[$row]) && in_array($col, $suppCellEligible[$row])) {
                    $suppCell[$row][] = $col;
                    // Remove column from eligibility
                    if(($key = array_search($col, $suppCellEligible[$row])) !== false) {
                      unset($suppCellEligible[$row][$key]);
                    }
                    // Remove single row/col from list
                    unset($suppSingleRow[$row]);
                    if(($key = array_search($col, $suppSingleCol)) !== false) {
                      unset($suppSingleCol[$key]);
                    }
                    // Add to complimentary group
                    $suppRowCount_this[$otherRows_str]++;
                    // Add to counts
                    // Dont add if suppression just happens down the row
                    if ($rowSettings[$row][$col]['valueSuppression']['complimentary'] !== "row") {
                      $suppRowCount[$row]++;
                    }
                    $suppColCount[$col]++;
                    // Mark row as suppressed
                    $colSuppressed = 1;
                    // Re-run Level 1 basic suppression
                    $suppArray = $this->processSuppression_Basic($rowSettings, $headers, $rowData, $suppCell, $suppCellEligible, $suppRowCount, $suppColCount, $suppRowOther, $suppColOther);
                    // Set the variables
                    $suppCell = $suppArray['suppCell'];
                    $suppCellEligible = $suppArray['suppCellEligible'];
                    $suppRowCount = $suppArray['suppRowCount'];
                    $suppColCount = $suppArray['suppColCount'];
                    $suppRowOther = $suppArray['suppRowOther'];
                    $suppColOther = $suppArray['suppColOther'];
                    // Recount groups
                    $suppRowGroups = $this->processSuppression_CountGroups($rowData, $suppCell, $col, $suppRowOther);
                    // Set variables from function
                    $suppRowCount_this = $suppRowGroups['suppRowCount_this'];
                    $suppRowTotal_this = $suppRowGroups['suppRowTotal_this'];
                  }
                }
              }
            }
          }
        }
        foreach($suppSingleCol as $numCol => $col) {
          // Attempt to suppress an eligible column with at least more than 1 suppression already in place.
          if (!$colSuppressed) {
            foreach($suppRowCount as $row => $count){
              // Check if cell allows row complimentary suppression down the row for the column
              if (isset($rowSettings[$row][$col]['valueSuppression']['complimentary'])
                && ($rowSettings[$row][$col]['valueSuppression']['complimentary']
                  && $rowSettings[$row][$col]['valueSuppression']['complimentary'] !== "column")) {
                $colSuppAllowed = true;
              }
              else {
                $colSuppAllowed = false;
              }
              // Check for complimentary row settings. If there is a list set and row is not in list, then not allowed.
              if (isset($rowSettings[$row])
                && isset($rowSettings[$row][$col])
                && isset($rowSettings[$row][$col]['valueSuppression'])
                && isset($rowSettings[$row][$col]['valueSuppression']['complimentary_row'])) {
                if (!in_array($row, $rowSettings[$row][$col]['valueSuppression']['complimentary_row'])) {
                  $colSuppAllowed = false;
                }
              }
              // See if we can complimentary suppress
              if (!$colSuppressed && $colSuppAllowed && $count > 0 && isset($suppCellEligible[$row]) && in_array($col, $suppCellEligible[$row])) {
                $suppCell[$row][] = $col;
                // Remove column from eligibility
                if(($key = array_search($col, $suppCellEligible[$row])) !== false) {
                  unset($suppCellEligible[$row][$key]);
                }
                // Remove single row/col from list
                unset($suppSingleRow[$row]);
                unset($suppSingleCol[$numCol]);
                // Add to counts
                // Dont add if suppression just happens down the row
                if ($rowSettings[$row][$col]['valueSuppression']['complimentary'] !== "row") {
                  $suppRowCount[$row]++;
                }
                $suppColCount[$col]++;
                // Mark row as suppressed
                $colSuppressed = 1;
                // Re-run Level 1 basic suppression
                $suppArray = $this->processSuppression_Basic($rowSettings, $headers, $rowData, $suppCell, $suppCellEligible, $suppRowCount, $suppColCount, $suppRowOther, $suppColOther);
                // Set the variables
                $suppCell = $suppArray['suppCell'];
                $suppCellEligible = $suppArray['suppCellEligible'];
                $suppRowCount = $suppArray['suppRowCount'];
                $suppColCount = $suppArray['suppColCount'];
                $suppRowOther = $suppArray['suppRowOther'];
                $suppColOther = $suppArray['suppColOther'];
              }
            }
          }
          // Attempt to suppress an eligible column by the least value
          if (!$colSuppressed) {
            // Get totals for each column in a row
            $suppRowTotal_this = [];
            foreach($suppRowCount as $row => $count){
              if (isset($rowData[$row]->$col) && is_numeric($rowData[$row]->$col)) {
                $suppRowTotal_this[$row] = $rowData[$row]->$col;
              }
            }
            // Order the items
            asort($suppRowTotal_this);
            // Do the loop
            foreach($suppRowTotal_this as $row => $total){
              // Check if cell allows row complimentary suppression down the row for the column
              if (isset($rowSettings[$row][$col]['valueSuppression']['complimentary'])
                && ($rowSettings[$row][$col]['valueSuppression']['complimentary']
                  && $rowSettings[$row][$col]['valueSuppression']['complimentary'] !== "column")) {
                $colSuppAllowed = true;
              }
              else {
                $colSuppAllowed = false;
              }
              // Check for complimentary row settings. If there is a list set and row is not in list, then not allowed.
              if (isset($rowSettings[$row])
                && isset($rowSettings[$row][$col])
                && isset($rowSettings[$row][$col]['valueSuppression'])
                && isset($rowSettings[$row][$col]['valueSuppression']['complimentary_row'])) {
                if (!in_array($row, $rowSettings[$row][$col]['valueSuppression']['complimentary_row'])) {
                  $colSuppAllowed = false;
                }
              }
              // See if we can complimentary suppress
              if (!$colSuppressed && $colSuppAllowed && isset($suppCellEligible[$row]) && in_array($col, $suppCellEligible[$row])
                && $total > 0) {
                $suppCell[$row][] = $col;
                // Remove column from eligibility
                if(($key = array_search($col, $suppCellEligible[$row])) !== false) {
                  unset($suppCellEligible[$row][$key]);
                }
                // Remove single row/col from list
                unset($suppSingleRow[$row]);
                if(($key = array_search($col, $suppSingleCol)) !== false) {
                  unset($suppSingleCol[$key]);
                }
                // Add to counts
                // Dont add if suppression just happens down the row
                if ($rowSettings[$row][$col]['valueSuppression']['complimentary'] !== "row") {
                  $suppRowCount[$row]++;
                }
                $suppColCount[$col]++;
                // Mark row as suppressed
                $colSuppressed = 1;
                // Re-run Level 1 basic suppression
                $suppArray = $this->processSuppression_Basic($rowSettings, $headers, $rowData, $suppCell, $suppCellEligible, $suppRowCount, $suppColCount, $suppRowOther, $suppColOther);
                // Set the variables
                $suppCell = $suppArray['suppCell'];
                $suppCellEligible = $suppArray['suppCellEligible'];
                $suppRowCount = $suppArray['suppRowCount'];
                $suppColCount = $suppArray['suppColCount'];
                $suppRowOther = $suppArray['suppRowOther'];
                $suppColOther = $suppArray['suppColOther'];
              }
            }
          }
          // Attempt to suppress an eligible column greater than zero
          if (!$colSuppressed) {
            foreach($suppRowCount as $row => $count){
              // Check if cell allows row complimentary suppression down the row for the column
              if (isset($rowSettings[$row][$col]['valueSuppression']['complimentary'])
                && ($rowSettings[$row][$col]['valueSuppression']['complimentary']
                  && $rowSettings[$row][$col]['valueSuppression']['complimentary'] !== "column")) {
                $colSuppAllowed = true;
              }
              else {
                $colSuppAllowed = false;
              }
              // Check for complimentary row settings. If there is a list set and row is not in list, then not allowed.
              if (isset($rowSettings[$row])
                && isset($rowSettings[$row][$col])
                && isset($rowSettings[$row][$col]['valueSuppression'])
                && isset($rowSettings[$row][$col]['valueSuppression']['complimentary_row'])) {
                if (!in_array($row, $rowSettings[$row][$col]['valueSuppression']['complimentary_row'])) {
                  $colSuppAllowed = false;
                }
              }
              // See if we can complimentary suppress
              if (!$colSuppressed && $colSuppAllowed
                && isset($suppCellEligible[$row]) && in_array($col, $suppCellEligible[$row])
                && $rowData[$row]->$col > 0) {
                $suppCell[$row][] = $col;
                // Remove column from eligibility
                if(($key = array_search($col, $suppCellEligible[$row])) !== false) {
                  unset($suppCellEligible[$row][$key]);
                }
                // Remove single row/col from list
                unset($suppSingleRow[$row]);
                if(($key = array_search($col, $suppSingleCol)) !== false) {
                  unset($suppSingleCol[$key]);
                }
                // Add to counts
                // Dont add if suppression just happens down the row
                if ($rowSettings[$row][$col]['valueSuppression']['complimentary'] !== "row") {
                  $suppRowCount[$row]++;
                }
                $suppColCount[$col]++;
                // Mark row as suppressed
                $colSuppressed = 1;
                // Re-run Level 1 basic suppression
                $suppArray = $this->processSuppression_Basic($rowSettings, $headers, $rowData, $suppCell, $suppCellEligible, $suppRowCount, $suppColCount, $suppRowOther, $suppColOther);
                // Set the variables
                $suppCell = $suppArray['suppCell'];
                $suppCellEligible = $suppArray['suppCellEligible'];
                $suppRowCount = $suppArray['suppRowCount'];
                $suppColCount = $suppArray['suppColCount'];
                $suppRowOther = $suppArray['suppRowOther'];
                $suppColOther = $suppArray['suppColOther'];
              }
            }
          }
          // Attempt to suppress an eligible column **Catchall
          if (!$colSuppressed) {
            foreach($suppRowCount as $row => $count){
              // Check if cell allows row complimentary suppression down the row for the column
              if (isset($rowSettings[$row][$col]['valueSuppression']['complimentary'])
                && ($rowSettings[$row][$col]['valueSuppression']['complimentary']
                  && $rowSettings[$row][$col]['valueSuppression']['complimentary'] !== "column")) {
                $colSuppAllowed = true;
              }
              else {
                $colSuppAllowed = false;
              }
              // Check for complimentary row settings. If there is a list set and row is not in list, then not allowed.
              if (isset($rowSettings[$row])
                && isset($rowSettings[$row][$col])
                && isset($rowSettings[$row][$col]['valueSuppression'])
                && isset($rowSettings[$row][$col]['valueSuppression']['complimentary_row'])) {
                if (!in_array($row, $rowSettings[$row][$col]['valueSuppression']['complimentary_row'])) {
                  $colSuppAllowed = false;
                }
              }
              // See if we can complimentary suppress
              if (!$colSuppressed && $colSuppAllowed && isset($suppCellEligible[$row]) && in_array($col, $suppCellEligible[$row])) {
                $suppCell[$row][] = $col;
                // Remove column from eligibility
                if(($key = array_search($col, $suppCellEligible[$row])) !== false) {
                  unset($suppCellEligible[$row][$key]);
                }
                // Remove single row/col from list
                unset($suppSingleRow[$row]);
                if(($key = array_search($col, $suppSingleCol)) !== false) {
                  unset($suppSingleCol[$key]);
                }
                // Add to counts
                // Dont add if suppression just happens down the row
                if ($rowSettings[$row][$col]['valueSuppression']['complimentary'] !== "row") {
                  $suppRowCount[$row]++;
                }
                $suppColCount[$col]++;
                // Mark row as suppressed
                $colSuppressed = 1;
                // Re-run Level 1 basic suppression
                $suppArray = $this->processSuppression_Basic($rowSettings, $headers, $rowData, $suppCell, $suppCellEligible, $suppRowCount, $suppColCount, $suppRowOther, $suppColOther);
                // Set the variables
                $suppCell = $suppArray['suppCell'];
                $suppCellEligible = $suppArray['suppCellEligible'];
                $suppRowCount = $suppArray['suppRowCount'];
                $suppColCount = $suppArray['suppColCount'];
                $suppRowOther = $suppArray['suppRowOther'];
                $suppColOther = $suppArray['suppColOther'];
              }
            }
          }
        }
      }
    }

    // If suppression, then apply to variable
    if (isset($suppCell)) {
      $this->rowSuppression = $suppCell;

      // Process actual suppressions
      if (!$suppOverride) {
        // Loop suppressions and do it
        foreach($suppCell as $row => $value) {
          foreach($value as $key => $field) {
            // Get character... if not exist, set default
            if (isset($suppCharacterOverride)) {
              $suppCharacter = $suppCharacterOverride;
            }
            elseif (isset($rowSettings[$row])
              && isset($rowSettings[$row][$field])
              && isset($rowSettings[$row][$field]['valueSuppression'])
              && isset($rowSettings[$row][$field]['valueSuppression']['character'])) {
              // Up to 6 characters in length
              $suppCharacter = substr($rowSettings[$row][$field]['valueSuppression']['character'], 6);
            }
            else {
              $suppCharacter = '+';
            }
            $rowData[$row]->$field = $suppCharacter;
          }
        }
      }
    }

    $processSuppression['rowData'] = $rowData;
    $processSuppression['rowSuppression'] = $this->rowSuppression;

    return $processSuppression;
  }

  /**
   * @param $rowData
   * @param $suppCell
   * @param $col
   * @param $suppRowOther
   * @return array
   */
  public function processSuppression_CountGroups($rowData, $suppCell, $col, $suppRowOther) {
    // Prep return array
    $suppRowGroups = [];
    $suppRowCount_this = [];
    // Get totals for each column in a row
    $suppRowTotal_this = [];

    foreach($suppRowOther[$col] as $row => $otherRows){
      $otherRows_str = implode("-", $otherRows);
      if (!isset($suppRowCount_this[$otherRows_str])) {
        $suppRowCount_this[$otherRows_str] = 0;
        $suppRowTotal_this[$otherRows_str] = [];
      }
      // If it is suppressed, count it
      if (isset($suppCell[$row]) && in_array($col, $suppCell[$row])) {
        $suppRowCount_this[$otherRows_str]++;
      }
      elseif (isset($rowData[$row]->$col) && is_numeric($rowData[$row]->$col)) {
        // Get value
        $suppRowTotal_this[$otherRows_str][$row] = $rowData[$row]->$col;
      }
    }
    // Set return array items
    $suppRowGroups['suppRowCount_this'] = $suppRowCount_this;
    $suppRowGroups['suppRowTotal_this'] = $suppRowTotal_this;
    // Return it
    return $suppRowGroups;
  }

  /**
   * @param $rowSettings
   * @param $headers
   * @param $rowData
   * @param $suppCell
   * @param $suppCellEligible
   * @param $suppRowCount
   * @param $suppColCount
   * @param $suppRowOther
   * @param $suppColOther
   * @return array
   */
  public function processSuppression_Basic($rowSettings, $headers, $rowData, $suppCell, $suppCellEligible, $suppRowCount, $suppColCount, $suppRowOther, $suppColOther) {
    $suppArray = [];
    $suppArray['suppCell'] = [];
    $suppArray['suppCellEligible'] = [];
    $suppArray['suppRowCount'] = [];
    $suppArray['suppColCount'] = [];
    $suppArray['suppRowOther'] = [];
    $suppArray['suppColOther'] = [];

    $current_user = \Drupal::currentUser();
    $roles = $current_user->getRoles();

    /*
     * Basic level 1 suppression
     *
     * This will review the items and check settings for anything marked as
     * suppressed. Those cells will be immediately suppressed.
     *
     * Level 1 includes items that are marked for other suppression.
     *
     */

    foreach ($headers as $field) {
      // Create a zero'd out column for count/total
      $suppColCount[$field] = 0;
    }

    if (is_array($rowData)) {
      for ($i = 0; $i < count($rowData); $i++) {
        // Create a zero'd out row for count/total
        $suppRowCount[$i] = 0;
        // Loop through the fields
        foreach ($headers as $field) {
          // Check if suppression is eligible on cell for complipmentary suppression
          $suppCellEligibleProcess = (
            isset($rowData[$i]->$field) && is_numeric($rowData[$i]->$field)
            && isset($rowSettings[$i][$field]['valueSuppression']) && !empty($rowSettings[$i][$field]['valueSuppression'])
            && isset($rowSettings[$i][$field]['valueSuppression']['complimentary']) && $rowSettings[$i][$field]['valueSuppression']['complimentary']
          );

          // Check if suppression is enabled for cell or if the cell is already marked for suppression
          $suppCellProcess = (
            isset($rowData[$i]->$field) && is_numeric($rowData[$i]->$field) && isset($rowSettings[$i][$field]['valueSuppression']) && !empty($rowSettings[$i][$field]['valueSuppression'])
            && ((isset($rowSettings[$i][$field]['valueSuppression']['min']) && $rowData[$i]->$field >= $rowSettings[$i][$field]['valueSuppression']['min']
              && isset($rowSettings[$i][$field]['valueSuppression']['max']) && $rowData[$i]->$field <= $rowSettings[$i][$field]['valueSuppression']['max']
              || isset($rowSettings[$i][$field]['valueSuppression']['any']) && $rowSettings[$i][$field]['valueSuppression']['any']))
            && (isset($rowSettings[$i][$field]['valueSuppression']['role']) && !empty(array_intersect($rowSettings[$i][$field]['valueSuppression']['role'], $roles))
              || !isset($rowSettings[$i][$field]['valueSuppression']['role']))
          );

          // if it is marked to suppress, then process it.
          // Also check the higher level rowSuppression variable set at end (rerun)
          if ((isset($suppCell[$i]) && in_array($field, $suppCell[$i]))
            || (isset($this->rowSuppression[$i]) && in_array($field, $this->rowSuppression[$i]))) {
            $suppCellProcess = true;
          }

          // Add to eligibility list if cell is eligible and not marked for suppression.
          if ($suppCellEligibleProcess && !$suppCellProcess) {
            $suppCellEligible[$i][] = $field;
          }

          // Complimentary grouping
          // Check for row groups
          if (isset($rowSettings[$i])
            && isset($rowSettings[$i][$field])
            && isset($rowSettings[$i][$field]['valueSuppression'])
            && isset($rowSettings[$i][$field]['valueSuppression']['complimentary_row'])) {
            if (!isset($suppRowOther[$field])) {
              $suppRowOther[$field] = [];
            }
            if (!in_array($i, $suppRowOther[$field])) {
              $suppRowOther[$field][$i] = $rowSettings[$i][$field]['valueSuppression']['complimentary_row'];
            }
          }
          // Check for column groups
          if (isset($rowSettings[$i])
            && isset($rowSettings[$i][$field])
            && isset($rowSettings[$i][$field]['valueSuppression'])
            && isset($rowSettings[$i][$field]['valueSuppression']['complimentary_column'])) {
            if (!in_array($field, $suppColOther[$field])) {
              $suppColOther[$i][] = [$field => $rowSettings[$i][$field]['valueSuppression']['complimentary_column']];
            }
          }

          // Check if suppression and for role. If suppressed, add it to suppression list.
          if ($suppCellProcess) {
            // Add the current cell to the suppression
            $suppCell[$i][] = $field;
            // Count it
            // Dont add if suppression just happens down the row
            if (isset($rowSettings[$i][$field]['valueSuppression'])
              && isset($rowSettings[$i][$field]['valueSuppression']['complimentary'])
              && $rowSettings[$i][$field]['valueSuppression']['complimentary'] !== "row") {
              $suppRowCount[$i]++;
            }
            // Dont add if suppression just happens across the columns
            if (isset($rowSettings[$i][$field]['valueSuppression'])
              && isset($rowSettings[$i][$field]['valueSuppression']['complimentary'])
              && $rowSettings[$i][$field]['valueSuppression']['complimentary'] !== "column") {
              $suppColCount[$field]++;
            }

            // Additional Suppression
            if (isset($rowSettings[$i][$field]['valueSuppression']['otherRowCol'])) {
              // Set the other Row/Col from setting
              $otherRowCol = $rowSettings[$i][$field]['valueSuppression']['otherRowCol'];
              // Loop through other Row/Column and set
              foreach($otherRowCol as $otherRow => $value) {
                foreach($value as $key => $otherField) {
                  if (is_numeric($otherRow) && isset($suppCell[$otherRow]) && !in_array($otherField,  $suppCell[$otherRow])) {
                    // Add for other row/column
                    $suppCell[$otherRow][] = $otherField;
                    // Dont add if suppression just happens down the row
                    if (isset($rowSettings[$otherRow][$otherField]['valueSuppression'])
                      && isset($rowSettings[$otherRow][$otherField]['valueSuppression']['complimentary'])
                      && $rowSettings[$otherRow][$otherField]['valueSuppression']['complimentary'] !== "row") {
                      $suppRowCount[$otherRow]++;
                    }
                    // Dont add if suppression just happens across the columns
                    if (isset($rowSettings[$otherRow][$otherField]['valueSuppression'])
                      && isset($rowSettings[$otherRow][$otherField]['valueSuppression']['complimentary'])
                      && $rowSettings[$otherRow][$otherField]['valueSuppression']['complimentary'] !== "column") {
                      $suppColCount[$otherField]++;
                    }
                  } elseif ($otherRow == 'current' && isset($suppCell[$i]) && !in_array($i,  $suppCell[$i])) {
                    // Add for other columns in current row
                    $suppCell[$i][] = $otherField;
                    $suppRowCount[$i]++;
                    $suppColCount[$otherField]++;
                    // Dont add if suppression just happens down the row
                    if (isset($rowSettings[$i][$otherField]['valueSuppression'])
                      && isset($rowSettings[$i][$otherField]['valueSuppression']['complimentary'])
                      && $rowSettings[$i][$otherField]['valueSuppression']['complimentary'] !== "row") {
                      $suppRowCount[$i]++;
                    }
                    // Dont add if suppression just happens across the columns
                    if (isset($rowSettings[$i][$otherField]['valueSuppression'])
                      && isset($rowSettings[$i][$otherField]['valueSuppression']['complimentary'])
                      && $rowSettings[$i][$otherField]['valueSuppression']['complimentary'] !== "column") {
                      $suppColCount[$otherField]++;
                    }
                  }
                }
              }
            }
          }
        }
      }
    }

    $suppArray['suppCell'] = $suppCell;
    $suppArray['suppCellEligible'] = $suppCellEligible;
    $suppArray['suppRowCount'] = $suppRowCount;
    $suppArray['suppColCount'] = $suppColCount;
    $suppArray['suppRowOther'] = $suppRowOther;
    $suppArray['suppColOther'] = $suppColOther;

    return $suppArray;
  }

  /**
   * @param $aggrid_id
   * @param $rowData
   * @param $suppCharacterOverride
   * @return mixed
   */
  public function suppressData($aggrid_id, $rowData, $suppCharacterOverride = NULL) {
    // Set the agGrid Configuration Helpers
    $aggridConfigHelpers = new AggridConfigHelpers();
    $aggridDefault = $aggridConfigHelpers->getDefaults($aggrid_id);
    
    $aggridRowSettings = $aggridDefault['aggridRowSettings'];

    // Set complimentary suppression variable
    // Check to see if complimentary is enabled on the default row and rowDefault
    if (isset($aggridRowSettings['default']['rowDefault']['valueSuppression']['complimentary'])) {
      $this->suppComplimentary = true;
    }
    else {
      $this->suppComplimentary = false;
    }

    // Get header information
    $getHeaders = $aggridConfigHelpers->getHeaders($aggridDefault['default']->columnDefs);
    $headers = $getHeaders['headers'];

    $rowSettings = $aggridConfigHelpers->getRowSettings($aggridRowSettings, $headers, $rowData, '');

    // Cycle through suppression multiple times
    if (is_array($rowData)) {
      // Clear it
      $this->rowSuppression = [];
      for ($i = 0; $i < 6; $i++) {
        $processSuppression = $this->processSuppression($rowSettings, $headers, $rowData, false, $this->suppComplimentary, $suppCharacterOverride);
        $rowData = $processSuppression['rowData'];
        $this->rowSuppression = $processSuppression['rowSuppression'];
      }
    }

    // Clear it
    $this->rowSuppression = [];

    return $processSuppression;
  }

}
