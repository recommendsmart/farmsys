<?php

namespace Drupal\basket;

/**
 * {@inheritdoc}
 */
class Requirements {

  /**
   * {@inheritdoc}
   */
  public function info(&$requirements) {
    $severity = REQUIREMENT_OK;
    $moduleInfo = \Drupal::service('extension.list.module')->getExtensionInfo('basket');
    $items = [];
    /*Mpdf*/
    if (class_exists('Mpdf\Mpdf')) {
      $items[] = [
        '#type'     => 'inline_template',
        '#template' => 'Mpdf: {{ \'Installed\'|t }}',
      ];
    }
    else {
      $severity = REQUIREMENT_WARNING;
      $items[] = [
        '#type'     => 'inline_template',
        '#template' => 'Mpdf: {{ \'Not installed\'|t }}. <a href="https://packagist.org/packages/mpdf/mpdf" target="_blank">{{ \'Install\'|t }}</a>',
      ];
    }
    /*PhpSpreadsheet*/
    if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
      $items[] = [
        '#type'     => 'inline_template',
        '#template' => 'PhpSpreadsheet: {{ \'Installed\'|t }}',
      ];
    }
    else {
      $severity = REQUIREMENT_WARNING;
      $items[] = [
        '#type'     => 'inline_template',
        '#template' => 'PhpSpreadsheet: {{ \'Not installed\'|t }}. <a href="https://phpspreadsheet.readthedocs.io" target="_blank">{{ \'Install\'|t }}</a>',
      ];
    }
    $requirements['basket'] = [
      'title'       => 'Basket',
      'value'       => !empty($moduleInfo['version']) ? $moduleInfo['version'] : NULL,
      'severity'    => $severity,
      'description' => [
        '#theme'      => 'item_list',
        '#items'      => $items,
      ],
    ];
  }

}
