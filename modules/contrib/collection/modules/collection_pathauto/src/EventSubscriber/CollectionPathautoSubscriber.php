<?php

namespace Drupal\collection_pathauto\EventSubscriber;

use Drupal\pathauto\PathautoGeneratorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\collection\Event\CollectionEvents;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\pathauto\PathautoFieldItemList;

/**
 * Class CollectionPathautoSubscriber.
 */
class CollectionPathautoSubscriber implements EventSubscriberInterface {

  /**
   * The Pathauto generator service.
   *
   * @var Drupal\pathauto\PathautoGeneratorInterface
   */
  protected $pathautoGenerator;

  /**
   * Constructs a new CollectionPathautoSubscriber.
   */
  public function __construct(PathautoGeneratorInterface $pathauto_generator) {
    $this->pathautoGenerator = $pathauto_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      CollectionEvents::COLLECTION_ITEM_ENTITY_CREATE => 'collectionItemCrud',
      CollectionEvents::COLLECTION_ITEM_ENTITY_UPDATE => 'collectionItemCrud',
      CollectionEvents::COLLECTION_ITEM_ENTITY_DELETE => 'collectionItemCrud',
    ];
  }

  /**
   * Process the COLLECTION_ITEM_ENTITY_CREATE, _UPDATE, and _DELETE events.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The dispatched event.
   */
  public function collectionItemCrud(Event $event) {
    $collected_item = $event->collectionItem->item->entity;

    // See if this collection item entity uses a pathauto alias.
    if ($collected_item instanceof ContentEntityInterface && $collected_item->path instanceof PathautoFieldItemList) {
      $this->pathautoGenerator->updateEntityAlias($collected_item, 'update');
    }
  }

}
