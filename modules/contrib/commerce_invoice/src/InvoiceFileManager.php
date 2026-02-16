<?php

namespace Drupal\commerce_invoice;

use Drupal\commerce_invoice\Entity\InvoiceInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Default implementation of the invoice file manager.
 */
class InvoiceFileManager implements InvoiceFileManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Entity print plugin manager.
   *
   * @var \Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface
   */
  protected $pluginManager;

  /**
   * The print builder.
   *
   * @var \Drupal\commerce_invoice\InvoicePrintBuilderInterface
   */
  protected $printBuilder;

  /**
   * Constructs a new InvoiceController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface $plugin_manager
   *   The Entity print plugin manager.
   * @param \Drupal\commerce_invoice\InvoicePrintBuilderInterface $print_builder
   *   The print builder.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityPrintPluginManagerInterface $plugin_manager, InvoicePrintBuilderInterface $print_builder) {
    $this->entityTypeManager = $entity_type_manager;
    $this->pluginManager = $plugin_manager;
    $this->printBuilder = $print_builder;
  }

  /**
   * Download an invoice file.
   *
   * @param \Drupal\commerce_invoice\Entity\InvoiceInterface $invoice
   *   The invoice.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the file was not found and could not be generated.
   */
  public function getInvoiceFile(InvoiceInterface $invoice) {
    if ($invoice->getFile()) {
      return $invoice->getFile();
    }

    // Check if an invoice was already generated for the given invoice,
    // that is not referenced by the invoice.
    $file = $this->loadExistingFile($invoice);
    // If the invoice file hasn't been generated yet, generate it.
    if (!$file) {
      $file = $this->generateInvoiceFile($invoice);
    }

    if (!$file) {
      throw new NotFoundHttpException();
    }

    // Sets the PDF file reference field on the invoice.
    if (!$invoice->getFile() || $invoice->getFile()->id() !== $file->id()) {
      $invoice->setFile($file);
      $invoice->save();
    }

    return $file;
  }

  /**
   * Generates a PDF file for the given invoice.
   *
   * @param \Drupal\commerce_invoice\Entity\InvoiceInterface $invoice
   *   The invoice.
   *
   * @return \Drupal\file\FileInterface|null
   *   The invoice file, NULL if the generation failed.
   */
  protected function generateInvoiceFile(InvoiceInterface $invoice) {
    try {
      /** @var \Drupal\entity_print\Plugin\PrintEngineInterface $print_engine */
      $print_engine = $this->pluginManager->createSelectedInstance('pdf');
      return $this->printBuilder->savePrintable($invoice, $print_engine);
    }
    catch (\Exception $e) {
      watchdog_exception('commerce_invoice', $e);
      return NULL;
    }
  }

  /**
   * Load an existing generated PDF file for the given invoice if it exist.
   *
   * @return \Drupal\file\FileInterface|null
   *   The invoice file, NULL if no matching invoice file was found or if it
   *   does not exist.
   */
  protected function loadExistingFile(InvoiceInterface $invoice) {
    /** @var \Drupal\File\FileStorageInterface $file_storage */
    $file_storage = $this->entityTypeManager->getStorage('file');
    // In case the invoice doesn't reference a file, fallback to loading a
    // file matching the given filename.
    $filename = $this->printBuilder->generateFilename($invoice);
    $langcode = $invoice->language()->getId();
    $files = $file_storage->loadByProperties([
      'uri' => "private://$filename",
      'langcode' => $langcode,
    ]);

    if (!$files) {
      return NULL;
    }

    /** @var \Drupal\File\FileInterface $file */
    $file = $file_storage->load(key($files));
    if (!file_exists($file->getFileUri())) {
      $file->delete();
      return NULL;
    }

    return $file;
  }

}
