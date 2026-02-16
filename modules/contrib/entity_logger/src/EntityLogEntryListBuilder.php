<?php

namespace Drupal\entity_logger;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * List builder for entity_log_entry entities.
 */
class EntityLogEntryListBuilder extends EntityListBuilder {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * View builder class for entity_log_entry entities.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $entityLogEntryViewBuilder;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new EntityLogEntryListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter, RendererInterface $renderer) {
    parent::__construct($entity_type, $entity_type_manager->getStorage($entity_type->id()));
    $this->dateFormatter = $date_formatter;
    $this->entityLogEntryViewBuilder = $entity_type_manager->getViewBuilder('entity_log_entry');
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['message'] = $this->t('Log entry');
    $header['severity'] = $this->t('Severity');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\entity_logger\Entity\EntityLogEntryInterface $entity */
    $row['id'] = $entity->id();
    $message = $this->entityLogEntryViewBuilder->view($entity);
    $row['message'] = $this->renderer->render($message);
    $severity_levels = RfcLogLevel::getLevels();
    $row['severity'] = $severity_levels[$entity->getSeverity()];
    $row['created'] = $this->dateFormatter->format($entity->getCreatedTime(), 'short');
    return $row + parent::buildRow($entity);
  }

}
