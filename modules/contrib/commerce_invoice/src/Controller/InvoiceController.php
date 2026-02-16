<?php

namespace Drupal\commerce_invoice\Controller;

use Drupal\commerce_invoice\InvoiceFileManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Provides the invoice download route.
 */
class InvoiceController implements ContainerInjectionInterface {

  use DependencySerializationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The invoice file manager.
   *
   * @var \Drupal\commerce_invoice\InvoiceFileManagerInterface
   */
  protected $invoiceFileManager;

  /**
   * Constructs a new InvoiceController object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\commerce_invoice\InvoiceFileManagerInterface $invoice_file_manager
   *   The invoice file manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, InvoiceFileManagerInterface $invoice_file_manager) {
    $this->configFactory = $config_factory;
    $this->invoiceFileManager = $invoice_file_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('commerce_invoice.invoice_file_manager')
    );
  }

  /**
   * Download an invoice.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the file was not found.
   */
  public function download(RouteMatchInterface $route_match) {
    /** @var \Drupal\commerce_invoice\Entity\InvoiceInterface $invoice */
    $invoice = $route_match->getParameter('commerce_invoice');

    $file = $this->invoiceFileManager->getInvoiceFile($invoice);
    $config = $this->configFactory->get('entity_print.settings');
    // Check whether we need to force the download.
    $content_disposition = $config->get('force_download') ? 'attachment' : NULL;
    $headers = file_get_content_headers($file);
    return new BinaryFileResponse($file->getFileUri(), 200, $headers, FALSE, $content_disposition);
  }

}
