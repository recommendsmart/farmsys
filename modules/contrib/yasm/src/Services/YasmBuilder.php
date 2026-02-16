<?php

namespace Drupal\yasm\Services;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Implements Yasm helpers.
 */
class YasmBuilder implements YasmBuilderInterface {

  /**
   * Default cols by row when show cards.
   */
  const DEFAULT_COLUMN_COLS = 5;

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function markup($content, $picto = NULL, array $class = []) {
    $build = [
      '#markup' => $this->picto($picto) . $content,
    ];
    if (!empty($class)) {
      return [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => ['class' => $class],
        'child' => $build,
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function picto($picto = NULL) {
    if (!empty($picto)) {
      return new FormattableMarkup('<i class="@picto"></i> ', [
        '@picto' => $picto,
      ]);
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function table($header, $rows, $chart_key = '') {
    $build = [];
    if (!empty($chart_key)) {
      // Add a chart key to inform that this table is chartable.
      $build['#yasm_chart'] = $chart_key;
    }
    $build['yasm_table'] = [
      '#type' => 'table',
      '#attributes' => [
        'class' => ['datatable', 'display'],
      ],
      '#header' => $header,
      '#rows'   => $rows,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function title(string $title, $picto = NULL, $class = ['title']) {
    return [
      '#markup' => new FormattableMarkup('<h4 class="@class">' . $this->picto($picto) . '@title</h4>', [
        '@title' => $title,
        '@class' => implode(' ', $class),
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function panel($content) {
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['panel', 'yasm-panel']],
      'child' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => ['class' => ['panel__content']],
        '#value' => $content,
      ],
    ];

    if (is_string($content)) {
      $build['#value'] = $content;
    }
    else {
      $build['child'] = $content;
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function card($picto, $label, $count) {
    return $this->picto($picto) . $label . ': ' . $count;
  }

  /**
   * {@inheritdoc}
   */
  public function getColumnClass($cols = NULL) {
    $colClass = [
      1 => ['layout-column', 'layout-column--full'],
      2 => ['layout-column', 'layout-column--half'],
      3 => ['layout-column', 'layout-column--one-third'],
      4 => ['layout-column', 'layout-column--quarter'],
      5 => ['layout-column', 'layout-column--fifth'],
    ];

    if (!$cols || !isset($colClass[$cols])) {
      return $colClass[self::DEFAULT_COLUMN_COLS];
    }

    return $colClass[$cols];
  }

  /**
   * {@inheritdoc}
   */
  public function column($content, $cols = NULL) {
    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => $this->getColumnClass($cols),
      ],
      'child' => $content,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function columns($cards, array $class = [], $max_cols = NULL) {
    $build = [];

    // Distribuite cards and columns (default maximum cols = 5).
    $max_cols = empty($max_cols) ? 5 : $max_cols;
    $cols = count($cards);
    $cols = ($cols > $max_cols) ? $max_cols : $cols;

    $columns = [];
    $column_index = 1;
    foreach ($cards as $card) {
      if (!empty($card)) {
        // If we have more cards than columns append cards to aviable column.
        if ($column_index > $cols) {
          $column_index = 1;
        }
        $columns[$column_index][] = $this->panel($card);
        $column_index++;
      }
    }

    $build = [];
    foreach ($columns as $column) {
      $build[] = $this->column($column, $cols);
    }

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => array_merge(['yasm-columns', 'yasm'], $class),
      ],
      'child' => $build,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLastMonths($year = NULL) {
    $max_date = (!empty($year) && is_numeric($year)) ? strtotime('31-12-' . $year) : time();
    $min_date = strtotime('first day of this month', $max_date);

    $return = [];
    for ($i = 0; $i <= 11; $i++) {
      $return[] = [
        'label' => date('m-Y', $min_date),
        'min'   => $min_date,
        'max'   => $max_date,
      ];
      // Prepare values for next round.
      $max_date = $min_date;
      $min_date = strtotime('-1 month', $min_date);
    }

    // Array reverse because we want to return dates from minus to max.
    return array_reverse($return);
  }

  /**
   * {@inheritdoc}
   */
  public function getIntervalFilter($key, $max, $min) {
    return [
      [
        'key'      => $key,
        'value'    => $max,
        'operator' => '<=',
      ],
      [
        'key'      => $key,
        'value'    => $min,
        'operator' => '>=',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getYearFilter($key, $year) {
    $max = strtotime('31-12-' . $year);
    $min = strtotime('01-01-' . $year);

    return $this->getIntervalFilter($key, $max, $min);
  }

  /**
   * {@inheritdoc}
   */
  public function getYearLinks($firstYear, $activeYear) {
    $prefix = '?year=';
    $currentYear = date('Y');

    $items = [];
    $items[] = [
      'link'  => $prefix . 'all',
      'label' => $this->t('All'),
    ];
    $activeLink = ($activeYear === 'all') ? $prefix . 'all' : '';

    for ($year = $firstYear; $year <= $currentYear; $year++) {
      $items[] = [
        'link'  => $prefix . $year,
        'label' => $year,
      ];
      if ($year == $activeYear) {
        $activeLink = $prefix . $year;
      }
    }

    return [
      '#theme'       => 'yasm_tabs',
      '#items'       => $items,
      '#active_link' => $activeLink,
    ];
  }

}
