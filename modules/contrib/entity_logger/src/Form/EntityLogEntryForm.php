<?php

namespace Drupal\entity_logger\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\entity_logger\Entity\EntityLogEntryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for adding a log entry to an entity.
 */
class EntityLogEntryForm extends ContentEntityForm {

  /**
   * Constructs a EntityLogEntryForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    if (isset($form['severity']['widget'][0]['value'])) {
      // Transform severity numeric field to select list with known log levels.
      $form['severity']['widget'][0]['value']['#type'] = 'select';
      $form['severity']['widget'][0]['value']['#options'] = RfcLogLevel::getLevels();
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_logger\Entity\EntityLogEntryInterface $entity */
    $entity = $this->getEntity();
    $target_entity = $entity->isNew() ? $this->getTargetEntity() : $entity->getTargetEntity();
    if (!$target_entity) {
      throw new \RuntimeException('No target entity found');
    }

    if ($entity instanceof EntityLogEntryInterface && $target_entity instanceof EntityInterface) {
      $entity->setTargetEntity($target_entity);
    }

    parent::save($form, $form_state);

    $this->messenger()->addMessage($this->t('The log entry has been successfully saved.'));

    $form_state->setRedirect("entity.{$target_entity->getEntityTypeId()}.entity_logger", [
      $target_entity->getEntityTypeId() => $target_entity->id(),
    ]);
  }

  /**
   * Get the target entity to attach this log entry to.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity to attach this log entry to.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getTargetEntity() {
    if (!$target_type = $this->getRouteMatch()->getParameter('entity_type')) {
      return NULL;
    }
    if (!$target_id = $this->getRouteMatch()->getParameter('entity')) {
      return NULL;
    }
    $target_entity_storage = $this->entityTypeManager->getStorage($target_type);
    if (!$target_entity_storage instanceof EntityStorageInterface) {
      return NULL;
    }

    $target_entity = $target_entity_storage->load($target_id);
    if (!$target_entity instanceof EntityInterface) {
      return NULL;
    }
    return $target_entity;
  }

}
