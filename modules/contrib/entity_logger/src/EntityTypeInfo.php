<?php

namespace Drupal\entity_logger;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines entity type information for entity_logger.
 */
class EntityTypeInfo implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity logger module settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $entityLoggerSettings;

  /**
   * EntityTypeInfo constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(AccountInterface $current_user, ConfigFactoryInterface $config_factory) {
    $this->currentUser = $current_user;
    $this->entityLoggerSettings = $config_factory->get('entity_logger.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('config.factory')
    );
  }

  /**
   * Add link templates to appropriate entity types.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   The master entity type list to alter.
   *
   * @see hook_entity_type_alter()
   */
  public function entityTypeAlter(array &$entity_types) {
    $enabled_entity_types = $this->entityLoggerSettings->get('enabled_entity_types');
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if (in_array($entity_type_id, $enabled_entity_types)) {
        $entity_type->setLinkTemplate('entity-logger', "/entity_logger/$entity_type_id/{{$entity_type_id}}");
      }
    }
  }

  /**
   * Add entity operation on entities that supports it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which to define an operation.
   *
   * @return array
   *   An array of operation definitions.
   *
   * @see hook_entity_operation()
   */
  public function entityOperation(EntityInterface $entity) {
    $operations = [];
    if ($this->currentUser->hasPermission('view entity log entries')) {
      if ($entity->hasLinkTemplate('entity-logger')) {
        $operations['entity_logger'] = [
          'title' => $this->t('Log'),
          'weight' => 50,
          'url' => $entity->toUrl('entity-logger'),
        ];
      }
    }
    return $operations;
  }

}
