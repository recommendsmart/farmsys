<?php

namespace Drupal\basket;

use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Url;
use Drupal\views\Views;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Drupal\Core\Render\Markup;

/**
 * {@inheritdoc}
 */
class BasketExport {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->basket = \Drupal::service('Basket');
  }

  /**
   * {@inheritdoc}
   */
  public function run($subType = NULL) {
    $returnVal = [];
    if (!class_exists(Spreadsheet::class)) {
      $returnVal = $this->basket->getError(404);
    }
    if (!\Drupal::currentUser()->hasPermission('basket access_export_order')) {
      $returnVal = $this->basket->getError(403);
    }
    $request = \Drupal::request()->query->all();
    switch ($subType) {
      case'finish':
        if (!empty($request['download'])) {
          $this->downloadExcel();
          exit;
        }
        $downloadUrl = Url::fromRoute('basket.admin.pages', ['page_type' => 'orders-export-finish'], ['query' => ['download' => 1]])->toString();
        $returnVal = [
          '#prefix'       => '<div class="basket_table_wrap" id="export_finish">',
          '#suffix'       => '</div>',
          [
            '#prefix'       => '<div class="b_title">',
            '#suffix'       => '</div>',
            '#markup'       => $this->basket->Translate()->t('Export orders'),
          ], [
            '#prefix'       => '<div class="b_content">',
            '#suffix'       => '</div>',
            '#type'         => 'inline_template',
            '#template'     => '<b>{{ title }}</b><br/>{{ text }}',
            '#context'      => [
              'title'         => $this->basket->Translate()->t('Export completed successfully.'),
              'text'          => $this->basket->Translate()->t('If the automatic download has not started - click on the @link@.', [
                '@link@'        => Markup::create('<a href="' . $downloadUrl . '">' . $this->basket->Translate()->t('link') . '</a>'),
              ]),
            ], [
              '#type'         => 'inline_template',
              '#template'     => '<br/><a href="javascript:void(0);" onclick="self.close()" class="download">{{text}}</a>',
              '#context'      => [
                'text'          => $this->basket->Translate()->t('Close page'),
              ],
            ],
          ], [
            '#markup'       => Markup::create('<script type="text/javascript">window.location.href="' . $downloadUrl . '";</script>'),
          ],
        ];

        break;

      default:
        $this->dataInfo('clear');
        $operations = [];
        if (!empty(Views::getEnabledViews()['basket'])) {
          if (!empty($export_fields = \Drupal::request()->query->get('export_fields'))) {
            \Drupal::request()->query->set('filter_fields', $export_fields);
          }
          $view = Views::getView('basket');
          $view->execute('block_1');
          if (!empty($view->result)) {
            foreach ($view->result as $row) {
              if (empty($row->basket_orders_id)) {
                continue;
              }
              $operations[] = [__CLASS__.'::process', [$row->basket_orders_id]];
            }
          }
        }
        if (!empty($operations)) {
          $batch = [
            'title'             => t('Export orders', [], ['context' => 'basket']),
            'operations'        => $operations,
            'basket_batch'      => TRUE,
          ];
          batch_set($batch);
          $response = batch_process(Url::fromRoute('basket.admin.pages', ['page_type' => 'orders-export-finish'])->toString());
          $response->send();
        }
        else {
          $returnVal = $this->basket->getError(404);
        }
        break;
    }
    return $returnVal;
  }

  /**
   * {@inheritdoc}
   */
  public static function process($orderId, &$context) {
    $basket = \Drupal::service('Basket');
    $order = $basket->orders($orderId)->load();
    $data = [
      'lines'     => [],
      'color'     => NULL,
    ];
    $context = [];
    foreach ($basket->getClass(__CLASS__)->getTokenInfo() as $keyTwig => $tokenTwig) {
      $context[$keyTwig] = $basket->Token()->getToken($keyTwig, [
        'order'     => $order,
      ]);
    }
    $config = $basket->getSettings('export_orders', 'config');
    $orderNode = NULL;
    if (!empty($order->nid)) {
      $orderNode = \Drupal::service('entity_type.manager')->getStorage('node')->load($order->nid);
    }
    if (empty($order->items)) {
      $order->items = [[]];
    }
    $lineKey = 0;
    foreach ($order->items as $row) {
      if (!empty($lineKey)) {
        $orderNode = NULL;
        $context = [];
      }
      foreach ($basket->getClass(__CLASS__)->getTokenInfo() as $keyTwig => $tokenTwig) {
        if (empty($tokenTwig['lineAll'])) {
          continue;
        }
        $context[$keyTwig] = $basket->Token()->getToken($keyTwig . '.' . $row->id, [
          'orderItem'     => $row,
          'order'         => $order,
        ]);
      }
      $data['lines'][$lineKey] = [];
      foreach ($config['orders']['data'] as $letter => $tokenText) {
        if (!empty(trim($tokenText))) {
          $tokenText = [
            '#type'     => 'inline_template',
            '#template' => $tokenText,
            '#context'  => $context,
          ];
          $data['lines'][$lineKey][$letter] = \Drupal::token()->replace(
            \Drupal::service('renderer')->render($tokenText), [
              'user'      => !empty($orderNode) ? \Drupal::service('entity_type.manager')->getStorage('user')->load($orderNode->get('uid')->target_id) : NULL,
              'node'      => $orderNode,
            ], [
              'clear'     => TRUE,
            ]
          );
        }
        else {
          $data['lines'][$lineKey][$letter] = NULL;
        }
      }
      $lineKey++;
    }
    if (!empty($data['lines'])) {
      \Drupal::database()->insert('basket_orders_export')
        ->fields([
          'uid'       => \Drupal::currentUser()->id(),
          'data'      => serialize($data),
        ])
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenInfo() {
    $info = drupal_get_path('module', 'basket') . '/config/basket_install/TwigExcelTokens.yml';
    $tokens = Yaml::decode(file_get_contents($info));
    // Alter.
    $templateType = 'orders_export';
    \Drupal::moduleHandler()->alter('basketTemplateTokens', $tokens, $templateType);
    // ---
    return $tokens;
  }

  /**
   * {@inheritdoc}
   */
  public function dataInfo($type) {
    $return = NULL;
    switch ($type) {
      case'clear':
        \Drupal::database()->delete('basket_orders_export')
          ->condition('uid', \Drupal::currentUser()->id())
          ->execute();
        break;

      case'load':
        $return = \Drupal::database()->select('basket_orders_export', 'f')
          ->fields('f', ['data'])
          ->condition('f.uid', \Drupal::currentUser()->id())
          ->orderBy('f.id', 'ASC')
          ->execute()->fetchCol();

        break;
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkOrderExport($orderId) {
    $context = $this->getLinkOrderExportContext($orderId);
    if (empty($context)) {
      return [];
    }
    return [
      '#type'         => 'inline_template',
      '#template'     => '<a href="{{ url }}" target="_blank" class="button--link"><span class="ico">{{ ico|raw }}</span> {{ text }}</a>',
      '#context'      => $this->getLinkOrderExportContext($orderId),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkOrderExportContext($orderId) {
    if (!\Drupal::currentUser()->hasPermission('basket access_export_order')) {
      return [];
    }
    $order = $this->basket->Orders($orderId)->load();
    if (empty($order->id) || !is_numeric($order->id)) {
      return [];
    }
    if (!class_exists(Spreadsheet::class)) {
      return [];
    }
    if (!empty($order->is_delete)) {
      return [];
    }
    return [
      'text'          => $this->basket->Translate()->t('Export'),
      'ico'           => $this->basket->getIco('export.svg'),
      'url'           => Url::fromRoute('basket.admin.pages', [
        'page_type'     => 'orders-export',
      ], [
        'query'         => [
          'export_fields' => ['id' => $orderId],
        ],
      ])->toString(),
      'target'        => '_blank',
    ];
  }

  /**
   * {@inheritdoc}
   */
  private function downloadExcel() {
    $config = $this->basket->getSettings('export_orders', 'config');
    $filename = $this->basket->translate()->t('Orders') . ' (' . date('d.m.Y H:i') . ').xlsx';
    $file_path = \Drupal::service('file_system')->realpath('temporary://'.$filename);
    /*Spreadsheet*/
    $spreadsheet = new Spreadsheet();
    $spreadsheet->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
    $sheet = $spreadsheet->getActiveSheet();
    /*Header*/
    $headerNum = 1;
    foreach ($config['orders']['header'] as $letter => $text) {
      if (empty(trim($text))) {
        continue;
      }
      $sheet->setCellValue($letter . '' . $headerNum, $this->basket->Translate()->trans(trim($text)));
    }
    // Fixed header.
    $sheet->freezePane('A2');
    /*Rows*/
    $rowsNum = $rowsNumStart = $headerNum + 1;
    $rows = self::dataInfo('load');
    $rowBorders = [];
    if (!empty($rows)) {
      foreach ($rows as $row) {
        $row = unserialize($row);
        if (empty($row['lines'])) {
          continue;
        }
        foreach ($row['lines'] as $line) {
          foreach ($line as $letter => $setValue) {
            if (empty(trim($setValue))) {
              continue;
            }
            $sheet->setCellValueExplicit($letter . '' . $rowsNum, trim($setValue), DataType::TYPE_STRING);
          }
          $rowsNum++;
        }
        $rowBorders[($rowsNum - 1)] = ($rowsNum - 1);
      }
    }
    /*Colors*/
    $highestColumn = $sheet->getHighestColumn();
    /*-- Header --*/
    $spreadsheet->getActiveSheet()->getStyle('A' . $headerNum . ':' . $highestColumn . '' . $headerNum)->applyFromArray([
      'font' => [
        'bold' => TRUE,
      ],
      'fill'      => [
        'fillType'  => Fill::FILL_SOLID,
        'color'     => [
          'rgb'       => '0569CC',
        ],
      ],
      'borders' => [
        'allBorders' => [
          'borderStyle' => Border::BORDER_THIN,
          'color' => ['argb' => '000000'],
        ],
      ],
    ]);
    $spreadsheet->getActiveSheet()->getStyle('A' . $headerNum . ':' . $highestColumn . '' . $headerNum)->getFont()->getColor()->setARGB('ffffff');
    /*-- Rows --*/
    if (!empty($rowBorders)) {
      foreach ($rowBorders as $rowBorderNum) {
        $spreadsheet->getActiveSheet()->getStyle('A' . $rowBorderNum . ':' . $highestColumn . '' . $rowBorderNum)->applyFromArray([
          'borders' => [
            'bottom' => [
              'borderStyle' => Border::BORDER_MEDIUM,
              'color' => ['argb' => '000000'],
            ],
          ],
        ]);
      }
    }
    $spreadsheet->getActiveSheet()->getStyle('A' . ($headerNum + 1) . ':' . $highestColumn . '' . $rowsNum)->getAlignment()->setWrapText(TRUE);
    /*Auto width*/
    foreach (range('A', $highestColumn) as $letter) {
      $spreadsheet->getActiveSheet()->getColumnDimension($letter)->setAutoSize(TRUE);
    }
    /*Save*/
    $writer = new Xlsx($spreadsheet);
    $writer->save($file_path);
    /*Download*/
    $response = new BinaryFileResponse($file_path, 200, [], FALSE, 'attachment');
    $response->deleteFileAfterSend(TRUE);
    $response->send();
  }

}
