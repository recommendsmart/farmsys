<?php

namespace Drupal\field_fallback\EventSubscriber;

use Drupal\Core\Field\FieldStorageDefinitionEvent;
use Drupal\Core\Field\FieldStorageDefinitionEvents;
use Drupal\field_fallback\FieldFallbackService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for field storage deletions.
 */
class FieldStorageSubscriber implements EventSubscriberInterface {

  /**
   * The field fallback service.
   *
   * @var \Drupal\field_fallback\FieldFallbackService
   */
  protected $fieldFallbackService;

  /**
   * Constructs a FieldStorageSubscriber object.
   *
   * @param \Drupal\field_fallback\FieldFallbackService $field_fallback_service
   *   The field fallback service.
   */
  public function __construct(FieldFallbackService $field_fallback_service) {
    $this->fieldFallbackService = $field_fallback_service;
  }

  /**
   * Listens to field storage deletions.
   *
   * When a base field is deleted, we check the field_fallback configs that
   * depend on the base field and clean the config.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionEvent $event
   *   The triggered event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onFieldStorageDeletion(FieldStorageDefinitionEvent $event): void {
    if ($event->getFieldStorageDefinition()->isBaseField()) {
      $this->fieldFallbackService->cleanupConfigBaseFields($event->getFieldStorageDefinition());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      FieldStorageDefinitionEvents::DELETE => 'onFieldStorageDeletion',
    ];
  }

}
