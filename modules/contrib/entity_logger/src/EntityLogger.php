<?php

namespace Drupal\entity_logger;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Service for logging to entities.
 */
class EntityLogger implements EntityLoggerInterface {

  /**
   * The entity_log_entry entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityLogEntryStorage;

  /**
   * The entity_logger module settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $entityLoggerSettings;

  /**
   * The message's placeholders parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected $parser;

  /**
   * EntityLogger constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, LogMessageParserInterface $parser) {
    $this->entityLogEntryStorage = $entity_type_manager->getStorage('entity_log_entry');
    $this->entityLoggerSettings = $config_factory->get('entity_logger.settings');
    $this->parser = $parser;
  }

  /**
   * {@inheritdoc}
   */
  public function log(EntityInterface $entity, string $message, array $context = [], int $severity = RfcLogLevel::INFO) {
    $enabled_entity_types = $this->entityLoggerSettings->get('enabled_entity_types');
    if (!in_array($entity->getEntityTypeId(), $enabled_entity_types)) {
      return NULL;
    }

    // Convert PSR3-style messages to \Drupal\Component\Render\FormattableMarkup
    // style, so they can be translated too in runtime.
    $context = $this->parser->parseMessagePlaceholders($message, $context);

    /** @var \Drupal\entity_logger\Entity\EntityLogEntryInterface $log_entry */
    $log_entry = $this->entityLogEntryStorage->create([]);
    $log_entry->setTargetEntity($entity);
    $log_entry->setMessage($message, $context);
    $log_entry->setSeverity($severity);
    $log_entry->save();
    return $log_entry;
  }

}
