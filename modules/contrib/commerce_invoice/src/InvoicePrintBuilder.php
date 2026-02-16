<?php

namespace Drupal\commerce_invoice;

use Drupal\commerce_invoice\Entity\InvoiceInterface;
use Drupal\commerce_invoice\Event\InvoiceEvents;
use Drupal\commerce_invoice\Event\InvoiceFilenameEvent;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_print\FilenameGeneratorInterface;
use Drupal\entity_print\Plugin\PrintEngineInterface;
use Drupal\entity_print\PrintBuilderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The print builder service.
 */
class InvoicePrintBuilder implements InvoicePrintBuilderInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity storage for the 'file' entity type.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * The Entity print builder.
   *
   * @var \Drupal\entity_print\PrintBuilderInterface
   */
  protected $printBuilder;

  /**
   * The Entity print filename generator.
   *
   * @var \Drupal\entity_print\FilenameGeneratorInterface
   */
  protected $filenameGenerator;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The Current User object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new InvoicePrintBuilder object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\entity_print\PrintBuilderInterface $print_builder
   *   The Entity print builder.
   * @param \Drupal\entity_print\FilenameGeneratorInterface $filename_generator
   *   The Entity print filename generator.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, PrintBuilderInterface $print_builder, FilenameGeneratorInterface $filename_generator, EventDispatcherInterface $event_dispatcher, AccountInterface $current_user) {
    $this->configFactory = $config_factory;
    $this->fileStorage = $entity_type_manager->getStorage('file');
    $this->printBuilder = $print_builder;
    $this->filenameGenerator = $filename_generator;
    $this->eventDispatcher = $event_dispatcher;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function generateFilename(InvoiceInterface $invoice) {
    $filename = $this->filenameGenerator->generateFilename([$invoice]);
    $filename .= '-' . $invoice->language()->getId() . '-' . str_replace('_', '', $invoice->getState()->getId());
    // Let the filename be altered.
    $event = new InvoiceFilenameEvent($filename, $invoice);
    $this->eventDispatcher->dispatch(InvoiceEvents::INVOICE_FILENAME, $event);
    $filename = $event->getFilename() . '.pdf';
    return $filename;
  }

  /**
   * {@inheritdoc}
   */
  public function savePrintable(InvoiceInterface $invoice, PrintEngineInterface $print_engine, $scheme = 'private') {
    $filename = $this->generateFilename($invoice);
    $config = $this->configFactory->get('entity_print.settings');
    $uri = $this->printBuilder->savePrintable([$invoice], $print_engine, $scheme, $filename, $config->get('default_css'));

    if (!$uri) {
      return FALSE;
    }

    $file = $this->fileStorage->create([
      'uri' => $uri,
      'uid' => $this->currentUser->id(),
      'langcode' => $invoice->language()->getId(),
      'status' => FILE_STATUS_PERMANENT,
    ]);
    $file->save();

    return $file;
  }

}
