<?php

namespace Drupal\content_as_config\Controller;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\Messenger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Parent class for per-entity-type content-as-config controllers.
 */
abstract class EntityControllerBase extends ControllerBase {

  const ENTITY_TYPE = '';
  const FIELD_NAMES = [];

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * The per-entity-type configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $entityConfig;

  /**
   * EntityControllerBase constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   The logger factory service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory, Messenger $messenger, LoggerChannelFactory $logger_factory) {
    $config_name = 'content_as_config.' . static::ENTITY_TYPE;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->messenger = $messenger;
    $this->loggerFactory = $logger_factory;
    $this->entityConfig = $config_factory->getEditable($config_name);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.factory'),
      $container->get('messenger'),
      $container->get('logger.factory')
    );
  }

  /**
   * Writes a message to Drupal's log.
   *
   * Declared static because it must be callable from a batch.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   The message to be written.
   * @param string $level
   *   The severity level. Defaults to 'notice'.
   * @param array $context
   *   Any context. Useful when running in a batch context.
   */
  public static function logMessage($message, string $level = 'notice', array $context = []): void {
    static $log;
    if (!isset($log)) {
      $log = \Drupal::configFactory()->get('content_as_config.config')->get('log') ?? TRUE;
    }
    if (!$log) {
      return;
    }
    \Drupal::logger('content_as_config')->log($level, (string) $message, $context);
  }

  /**
   * Fetches entities which are to be exported.
   *
   * @param array|null $export_list
   *   A list of exportable identifiers.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   An array of entities.
   */
  protected function getExportableEntities(?array $export_list): array {
    $entities = [];
    $storage = $this->entityTypeManager->getStorage(static::ENTITY_TYPE);
    if (isset($export_list)) {
      $export_list = array_filter($export_list, 'is_string');
      if (!empty($export_list)) {
        $entities = $storage->loadByProperties(['uuid' => $export_list]);
      }
    }
    else {
      $entities = $storage->loadMultiple();
    }
    return $entities;
  }

  /**
   * Filters configured items which are to be imported.
   *
   * @param array $import_list
   *   A list of configuration item descriptors which are to be imported.
   * @param array $all_items
   *   All items present in the configuration.
   *
   * @return array
   *   The configuration items which are to be imported.
   */
  protected function getImportableItems(array $import_list, array $all_items): array {
    $items = [];
    foreach ($all_items as $item) {
      if (isset($import_list[$item['uuid']])) {
        $items[] = $item;
      }
    }
    return $items;
  }

  /**
   * Exports a list of entities to configuration.
   *
   * @param array $form
   *   The form that is being submitted.
   * @param \Drupal\Core\Form\FormStateInterface|null $form_state
   *   The state of the form that is being submitted.
   *
   * @return int
   *   The number of entities that were exported.
   */
  public function export(array $form = [], FormStateInterface $form_state = NULL): int {
    static::logMessage($this->t('@et export started.', ['@et' => static::ENTITY_TYPE]));

    if ($form_state instanceof FormStateInterface && $form_state->hasValue('export_list')) {
      $export_list = $form_state->getValue('export_list');
    }
    else {
      $export_list = NULL;
    }
    $entities = $this->getExportableEntities($export_list);

    $this->entityConfig->initWithData([]);
    foreach ($entities as $entity) {
      $entity_info = ['uuid' => $entity->uuid()];
      foreach (static::FIELD_NAMES as $field_name) {
        $entity_info[$field_name] = $entity->get($field_name)->value;
      }
      $fields = $this->entityFieldManager->getFieldDefinitions(static::ENTITY_TYPE, $entity->bundle());

      $fields = array_filter($fields, function ($fld) {
        return !$fld->getFieldStorageDefinition()->isBaseField();
      });

      foreach ($fields as $field) {
        $fieldName = $field->getName();
        $entity_info['fields'][$fieldName] = $entity->$fieldName->getValue();
      }
      $this->entityConfig->set($entity->uuid(), $entity_info);

      static::logMessage($this->t('Exported @et %label.', [
        '@et' => static::ENTITY_TYPE,
        '%label' => $entity->label(),
      ]));
    }
    $this->entityConfig->save();

    $status_message = $this->formatPlural(
      count($entities),
      'One @et has been successfully exported.',
      '@ct @ets have been successfully exported.',
      [
        '@ct' => count($entities),
        '@et' => static::ENTITY_TYPE,
        '@ets' => static::ENTITY_TYPE . 's',
      ]
    );

    $this->messenger->addStatus($status_message);
    static::logMessage($this->t('@et export completed.', ['@et' => static::ENTITY_TYPE]));
    return count($entities);
  }

  /**
   * Imports content entities from configuration.
   *
   * @param array $form
   *   The form whose data is being submitted.
   * @param \Drupal\Core\Form\FormStateInterface|null $form_state
   *   The state of the form that is being submitted.
   *
   * @return int
   *   The number of items imported.
   */
  public function import(array $form = [], FormStateInterface $form_state = NULL): int {
    static::logMessage($this->t('@et import started.', ['@et' => static::ENTITY_TYPE]));

    if ($form_state instanceof FormStateInterface && $form_state->hasValue('import_list')) {
      $import_list = $form_state->getValue('import_list');
      $import_list = array_filter($import_list, 'is_string');
    }
    if (array_key_exists('style', $form)) {
      $style = $form['style'];
    }
    else {
      static::logMessage(
        $this->t('No style defined on @et import', ['@et' => static::ENTITY_TYPE]),
        'error'
      );
      return 0;
    }
    static::logMessage($this->t('Using style %style for @et import',
      ['%style' => $style, '@et' => static::ENTITY_TYPE]
    ));

    $configured_items = $this->entityConfig->get();

    if (isset($import_list)) {
      $items = $this->getImportableItems($import_list, $configured_items);
    }
    else {
      $items = $configured_items;
    }

    if (empty($items)) {
      $this->messenger->addWarning($this->t('No entities are available for import.'));
      return 0;
    }

    if (array_key_exists('drush', $form) && $form['drush'] === TRUE) {
      $context = ['drush' => TRUE];
      switch ($style) {
        case 'full':
          static::deleteDeletedItems($items, $context);
          static::importFull($items, $context);
          break;

        case 'force':
          static::deleteItems($context);
          static::importForce($items, $context);
          break;

        default:
          static::importSafe($items, $context);
          break;

      }
      $this->importFinishedCallback();
      return count($items);
    }

    $batch = ['title' => $this->t('Importing @et entities...', ['@et' => static::ENTITY_TYPE])];
    $prefix = '\\' . static::class . '::';
    switch ($style) {
      case 'full':
        $batch['operations'] = [
          [
            $prefix . 'deleteDeletedItems',
            [$items],
          ],
          [
            $prefix . 'importFull',
            [$items],
          ],
        ];
        break;

      case 'force':
        $batch['operations'] = [
          [
            $prefix . 'deleteItems',
            [],
          ],
          [
            $prefix . 'importForce',
            [$items],
          ],
        ];
        break;

      default:
        $batch['operations'] = [
          [
            $prefix . 'importSafe',
            [$items],
          ],
        ];
        break;
    }
    $batch['finished'] = $prefix . 'importFinishedCallback';
    batch_set($batch);
    return count($items);
  }

  /**
   * Deletes any entities of the given type that are not in config.
   *
   * Declared static because it must be callable from a batch.
   *
   * @param array $items
   *   List of entity descriptors to be deleted.
   * @param array $context
   *   Any batch context that may be available.
   */
  public static function deleteDeletedItems(array $items, array &$context): void {
    $uuids = [];
    foreach ($items as $item) {
      $uuids[] = $item['uuid'];
    }
    $count = 0;
    if (!empty($uuids)) {
      $query = \Drupal::entityQuery(static::ENTITY_TYPE);
      $query->condition('uuid', $uuids, 'NOT IN');
      $ids = $query->execute();
      $storage = \Drupal::entityTypeManager()->getStorage(static::ENTITY_TYPE);
      $entities = $storage->loadMultiple($ids);
      $count = count($entities);
      $storage->delete($entities);
    }
    if ($count > 0) {
      static::logMessage(\Drupal::translation()->formatPlural(
        $count,
        'Deleted 1 @et entity that was not in config.',
        'Deleted @ct @et entities that were not in config.',
        ['@ct' => $count, '@et' => static::ENTITY_TYPE]
      ));
    }
  }

  /**
   * Imports content entities, updating any that may already exist.
   *
   * Declared static because it must be callable from a batch.
   *
   * @param array $items
   *   List of entity descriptors to be imported.
   * @param array $context
   *   Any batch context that may be available.
   */
  public static function importFull(array $items, array &$context): void {
    $uuids = [];
    foreach ($items as $item) {
      $uuids[] = $item['uuid'];
    }
    $entities = [];
    if (!empty($uuids)) {
      $query = \Drupal::entityQuery(static::ENTITY_TYPE);
      $query->condition('uuid', $uuids, 'IN');
      $ids = $query->execute();
      $storage = \Drupal::entityTypeManager()->getStorage(static::ENTITY_TYPE);
      $entities = $storage->loadMultiple($ids);
    }
    $context['sandbox']['max'] = count($items);
    $context['sandbox']['progress'] = 0;

    foreach ($items as $item) {
      $query = \Drupal::entityQuery(static::ENTITY_TYPE);
      $query->condition('uuid', $item['uuid']);
      $ids = $query->execute();

      if (empty($ids)) {
        $entity = static::arrayToEntity($item);
        static::logMessage(
          t('Imported @et %label',
            ['@et' => static::ENTITY_TYPE, '%label' => $entity->label()]),
          'notice',
          $context
        );
      }
      else {
        foreach ($entities as $entity) {
          if ($item['uuid'] === $entity->uuid()) {
            $entity = static::arrayToEntity($item, $entity);
            static::logMessage(
              t('Updated @et %label',
                ['@et' => static::ENTITY_TYPE, '%label' => $entity->label()]
              ),
              'notice',
              $context
            );
            break;
          }
        }
      }

      $context['sandbox']['progress']++;
      if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
        $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
      }
    }
    $context['finished'] = 1;
  }

  /**
   * Imports only items which do not correspond to already-existing content.
   *
   * Declared static because it must be callable from a batch.
   *
   * @param array $items
   *   List of entity descriptors to be imported.
   * @param array $context
   *   Any batch context that may be available.
   */
  public static function importSafe(array $items, array &$context): void {
    $entities = \Drupal::entityTypeManager()->getStorage(static::ENTITY_TYPE)->loadMultiple();
    $filtered_items = array_filter($items, function ($item) use ($entities) {
      foreach ($entities as $entity) {
        if ($entity->uuid() === $item['uuid']) {
          return FALSE;
        }
      }
      return TRUE;
    });
    static::importForce($filtered_items, $context);
  }

  /**
   * Imports all items, assuming that none of them already exist.
   *
   * Declared static because it must be callable from a batch.
   *
   * @param array $items
   *   List of entity descriptors to be imported.
   * @param array $context
   *   Any batch context that may be available.
   */
  public static function importForce(array $items, array &$context): void {
    foreach ($items as $item) {
      $entity = self::arrayToEntity($item);
      static::logMessage(t('Imported @et %label',
        [
          '@et' => static::ENTITY_TYPE,
          '%label' => $entity->label(),
        ]),
        'notice',
        $context
      );
    }
  }

  /**
   * Deletes all entities of the configured type.
   *
   * Declared static because it must be callable from a batch.
   */
  public static function deleteItems(array &$context): void {
    $storage = \Drupal::entityTypeManager()->getStorage(static::ENTITY_TYPE);
    $entities = $storage->loadMultiple();
    $storage->delete($entities);

    static::logMessage(
      t('Deleted all @et entities.', ['@et' => static::ENTITY_TYPE]),
      'notice',
      $context
    );
  }

  /**
   * Batch-finished callback after import.
   *
   * Declared static because it must be callable from a batch.
   */
  protected static function importFinishedCallback(): void {
    static::logMessage(t('Flushing all caches'));
    drupal_flush_all_caches();
    static::logMessage(t(
      'Successfully flushed caches and imported @et entities.',
      ['@et' => static::ENTITY_TYPE]
    ));
    \Drupal::messenger()->addStatus(t(
      'Successfully imported @et entities.',
      ['@et' => static::ENTITY_TYPE]
    ));
  }

  /**
   * Converts an array to a content entity and saves it to the database.
   *
   * Declared static because it must be callable from a batch.
   *
   * @param array $info
   *   The configuration array.
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $entity
   *   The entity to be updated, if any.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The created/updated entity.
   */
  protected static function arrayToEntity(array $info, ?ContentEntityInterface $entity = NULL): ContentEntityInterface {
    if (!isset($entity)) {
      $entity = \Drupal::entityTypeManager()
        ->getStorage(static::ENTITY_TYPE)
        ->create(['uuid' => $info['uuid']]);
    }
    foreach (static::FIELD_NAMES as $field_name) {
      $entity->set($field_name, $info[$field_name]);
    }
    if (array_key_exists('fields', $info)) {
      foreach ($info['fields'] as $name => $value) {
        $entity->set($name, $value);
      }
    }
    $entity->save();
    return $entity;
  }

}
