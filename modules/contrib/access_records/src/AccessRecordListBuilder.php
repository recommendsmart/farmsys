<?php

namespace Drupal\access_records;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for the access record entity type.
 */
class AccessRecordListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a new AccessRecordListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter, RedirectDestinationInterface $redirect_destination) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $date_formatter;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['table'] = parent::render();

    $total = $this->getStorage()
      ->getQuery()
      ->count()
      ->accessCheck(TRUE)
      ->execute();

    $build['summary']['#markup'] = $this->t('Total access records: @total', ['@total' => $total]);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['ar_id'] = $this->t('ID');
    $header['ar_label'] = $this->t('Label');
    $header['ar_uid'] = $this->t('Created by');
    $header['ar_created'] = $this->t('Created');
    $header['ar_changed'] = $this->t('Updated');
    $header['ar_changed_uid'] = $this->t('Updated by');
    $header['ar_enabled'] = $this->t('Enabled');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $label = $entity->label();
    $rel = $entity->hasLinkTemplate('canonical') ? 'canonical' : 'edit-form';
    $op = $rel === 'canonical' ? 'view' : 'update';
    /** @var \Drupal\access_records\AccessRecordInterface $entity */
    $row['ar_id'] = $entity->access($op) ? $entity->toLink($entity->id(), $rel) : $entity->id();
    $row['ar_label'] = $label !== '' && $entity->access($op) ? $entity->toLink($label, $rel) : $label;
    $row['ar_uid']['data'] = [
      '#theme' => 'username',
      '#account' => $entity->getOwner(),
    ];
    $row['ar_created'] = $this->dateFormatter->format($entity->getCreatedTime());
    $row['ar_changed'] = $this->dateFormatter->format($entity->getChangedTime());
    $row['ar_changed_uid']['data'] = [
      '#theme' => 'username',
      '#account' => $entity->getChangedBy(),
    ];
    $row['ar_enabled'] = $entity->isEnabled() ? $this->t('Yes') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $destination = $this->redirectDestination->getAsArray();
    foreach ($operations as $key => $operation) {
      $operations[$key]['query'] = $destination;
    }
    return $operations;
  }

}
