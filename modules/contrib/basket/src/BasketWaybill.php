<?php

namespace Drupal\basket;

use Mpdf\Mpdf;
use Drupal\Core\Url;

/**
 * {@inheritdoc}
 */
class BasketWaybill {

  /**
   * Set basket.
   *
   * @var Drupal\basket\Basket
   */
  protected $basket;

  /**
   * Set isClass.
   *
   * @var bool
   */
  protected $isClass;

  /**
   * Set order.
   *
   * @var object
   */
  protected $order;

  /**
   * Set orderNode.
   *
   * @var object
   */
  protected $orderNode;

  /**
   * Set pdfMargins.
   *
   * @var int
   */
  protected static $pdfMargins = 5;

  /**
   * {@inheritdoc}
   */
  public function __construct($orderId = NULL) {
    $this->basket = \Drupal::service('Basket');
    $this->isClass = class_exists(Mpdf::class);
    $this->order = $this->basket->Orders($orderId)->load();
    if (!empty($this->order->nid)) {
      $this->orderNode = \Drupal::service('entity_type.manager')->getStorage('node')->load($this->order->nid);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLink() {
    if (!$this->isClass || empty($this->order->nid)) {
      return [];
    }
    return [
      '#type'         => 'inline_template',
      '#template'     => '<a href="{{ url }}" target="_blank" class="button--link"><span class="ico">{{ ico|raw }}</span> {{ text }}</a>',
      '#context'     => self::getLinkArray(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkArray() {
    if (!$this->isClass || empty($this->order->nid)) {
      return [];
    }
    return [
      'text'          => $this->basket->Translate()->t('Waybill'),
      'ico'           => $this->basket->getIco('pdf.svg'),
      'url'           => Url::fromRoute('basket.admin.pages', [
        'page_type'     => 'orders-waybill-' . $this->order->id,
      ])->toString(),
      'target'        => '_blank',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPdfView() {
    if (!$this->isClass || empty($this->order)) {
      return $this->basket->getError(404);
    }
    $mpdf = new Mpdf([
      'setAutoTopMargin'      => 'pad',
      'margin_left'           => self::$pdfMargins,
      'margin_right'          => self::$pdfMargins,
      'margin_top'            => self::$pdfMargins,
      'margin_bottom'         => self::$pdfMargins,
      'margin_header'         => self::$pdfMargins,
      'margin_footer'         => self::$pdfMargins,
      'tempDir'               => \Drupal::service('file_system')->realpath('temporary://'),
    ]);
    $mpdf->fontdata['Arial'] = [
      'R'                     => drupal_get_path('module', 'basket') . '/misc/fonts/Arial.ttf',
    ];
    $mpdf->SetDefaultFont('Arial');
    // Set header.
    $settingsHeader = $this->basket->getSettings('templates', 'waybill_header');
    $header = [
      '#type'         => 'inline_template',
      '#template'     => !empty($settingsHeader['config']['template']) ? $settingsHeader['config']['template'] : '',
      '#context'      => $this->basket->MailCenter()->getContext('waybill_header', [
        'order'         => $this->order,
      ]),
    ];
    $header = \Drupal::token()->replace(
      \Drupal::service('renderer')->render($header), [
        'user'      => !empty($this->orderNode) ? \Drupal::service('entity_type.manager')->getStorage('user')->load($this->orderNode->get('uid')->target_id) : NULL,
        'node'      => $this->orderNode,
      ], [
        'clear'     => TRUE,
      ]
    );
    $mpdf->SetHTMLHeader($header);
    // Set body.
    $settingsBody = $this->basket->getSettings('templates', 'waybill');
    $html = [
      '#theme'        => 'basket_waybill',
      '#info'         => $this->basket->MailCenter()->getContext('waybill', [
        'order'         => $this->order,
      ]),
    ];
    $html['#info']['body'] = [
      '#type'         => 'inline_template',
      '#template'     => !empty($settingsBody['config']['template']) ? $settingsBody['config']['template'] : '',
      '#context'      => $html['#info'],
    ];
    $html = \Drupal::token()->replace(
      \Drupal::service('renderer')->render($html), [
        'user'      => !empty($this->orderNode) ? \Drupal::service('entity_type.manager')->getStorage('user')->load($this->orderNode->get('uid')->target_id) : NULL,
        'node'      => $this->orderNode,
      ], [
        'clear'     => TRUE,
      ]
    );
    $mpdf->WriteHTML($html);

    $title = $this->basket->Translate()->t('Order ID: @num@', ['@num@' => $this->basket->Orders($this->order->id)->getId()]);
    $mpdf->SetTitle($title);
    $mpdf->Output($title . '.pdf', 'I');
    exit;
  }

}
