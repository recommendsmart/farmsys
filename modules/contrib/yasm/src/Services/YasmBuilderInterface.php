<?php

namespace Drupal\yasm\Services;

/**
 * Defines yasm utility interface.
 */
interface YasmBuilderInterface {

  /**
   * Build yasm markup.
   */
  public function markup($content, $picto = NULL, array $class = []);

  /**
   * Build fontawesome picto.
   */
  public function picto($picto = NULL);

  /**
   * Build yasm table.
   */
  public function table($header, $rows, $chart_key = '');

  /**
   * Build yasm titles.
   */
  public function title(string $title, $picto = NULL, $class = []);

  /**
   * Build panel container.
   */
  public function panel($content);

  /**
   * Get columns classes array.
   */
  public function getColumnClass($cols = NULL);

  /**
   * Build column container.
   */
  public function column($content, $cols = NULL);

  /**
   * Build multiple columns with auto calculated cols.
   */
  public function columns($cards, array $class = [], $max_cols = NULL);

  /**
   * Get last year timestamp months starting in the first day of every month.
   */
  public function getLastMonths($year);

  /**
   * Get interval filter from data array value.
   */
  public function getIntervalFilter($key, $max, $min);

  /**
   * Get interval year filter.
   */
  public function getYearFilter($key, $year);

  /**
   * Get year array build links from first year to current year.
   */
  public function getYearLinks($first_year, $active_year);

}
