<?php

namespace Drupal\entity_inherit\EntityInheritQueue;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_inherit\EntityInherit;
use Drupal\entity_inherit\Utilities\FriendTrait;

/**
 * A queue.
 */
class EntityInheritQueue implements \Countable {

  use FriendTrait;
  use StringTranslationTrait;

  /**
   * The queueable items.
   *
   * @var array
   */
  protected $items;

  /**
   * The EntityInherit singleton (service).
   *
   * @var \Drupal\entity_inherit\EntityInherit
   */
  protected $app;

  /**
   * Constructor.
   *
   * @param \Drupal\entity_inherit\EntityInherit $app
   *   The application singleton.
   */
  public function __construct(EntityInherit $app) {
    $this->friendAccess([EntityInherit::class]);
    $this->app = $app;
    $this->items = [];
  }

  /**
   * Add items to queue.
   *
   * @param array $items
   *   Items to add to the queue, for example ['node:1', 'node:2'].
   * @param array $original_fields
   *   Original field values of the parent field(s), for example:
   *   ['node.field_x' => [['value' => 'hello']]]. node:1 and node:2 will have
   *   each field value changed to the values in $changed_fields if their
   *   original field values are these.
   * @param array $changed_fields
   *   New field values of the parent field(s), for example:
   *   ['node.field_x' => [['value' => 'hi']]]. node:1 and node:2 will have each
   *   field value changed to these values if their original field values are
   *   the ones in $original_fields.
   */
  public function add(array $items, array $original_fields, array $changed_fields) {
    $queue = $this->getItems();

    foreach ($items as $item) {
      if (!array_key_exists($item, $queue['items'])) {
        $queue['items'][$item] = [
          'id' => $item,
          'original' => $original_fields,
        ];
      }
      foreach ($original_fields as $key => $original_field) {
        if (!array_key_exists($key, $queue['items'][$item]['original'])) {
          $queue['items'][$item]['original'][$key] = $original_field;
        }
      }
      foreach ($changed_fields as $key => $changed_field) {
        $queue['items'][$item]['changed'][$key] = $changed_field;
      }
    }

    $this->setItems($queue);
  }

  /**
   * Check if the queue contains an item by id.
   *
   * @param string $id
   *   An item such as node:1.
   *
   * @return bool
   *   TRUE if the queue contains this item.
   */
  public function contains(string $id) : bool {
    return array_key_exists($id, $this->getItems());
  }

  /**
   * Get the number of items in the queue.
   *
   * @return int
   *   Number of items in the queue.
   */
  public function count() : int {
    return count($this->getItems()['items']);
  }

  /**
   * Get queue items from the state variable.
   *
   * @return array
   *   An array which represents the queue of entities to manage.
   */
  public function getItems() : array {
    return $this->app->stateGetArray('entity_inherit_queue', [
      'items' => [],
    ]);
  }

  /**
   * Get the next item to process and remove it from the queue.
   *
   * @return array
   *   An item, or an empty array if there is none. The item should contain
   *   a unique id.
   */
  public function next() : array {
    $queue = $this->getItems();

    $return = array_shift($queue['items']);

    $this->setItems($queue);

    return $return ?: [];
  }

  /**
   * Get a processor.
   */
  public function processor() {
    return $this->app->singleton(EntityInheritQueueProcessorFactory::class)->processor($this);
  }

  /**
   * Process the queue.
   */
  public function process() {
    $this->processor()->process();
  }

  /**
   * Process the next item in the queue.
   *
   * @return string
   *   The name of the item which was processed; empty string if none..
   */
  public function processNext() : string {
    $next = $this->next();

    if (array_key_exists('id', $next)) {
      $this->processSingle($next);
      return $next['id'];
    }
    return '';
  }

  /**
   * Process an item as represented by an array.
   *
   * @param array $item
   *   An item represented by a queue array. The format for this needs to
   *   look like:
   *      (
   *          [id] => node:22
   *          [original] => Array
   *              (
   *                  [node.body] => Array
   *                      (
   *                          [0] => Array
   *                              (
   *                                  [value] => Changed in parent, should
   *                                             propagate to child.
   *                                  [summary] =>
   *                                  [format] => full_html
   *                              )
   *
   *                      )
   *
   *                  [node.field_parents] => Array
   *                      (
   *                      )
   *
   *              )
   *
   *          [changed] => Array
   *              (
   *                  [node.body] => Array
   *                      (
   *                          [0] => Array
   *                              (
   *                                  [format] => full_html
   *                                  [summary] =>
   *                                  [value] => Whats up?
   *                              )
   *
   *                      )
   *
   *                  [node.field_parents] => Array
   *                      (
   *                      )
   *
   *              )
   *
   *      ).
   */
  public function processSingle(array $item) {
    try {
      $this->app->getEntityFactory()->fromQueueableItem($item)->process($item);
    }
    catch (\Throwable $t) {
      $this->app->watchdogAndUserError($t, $this->t('Entity Inherit could not process the item @i.', ['@i' => isset($item['id']) ? $item['id'] : $this->t('[id not available]')]));
    }
  }

  /**
   * Set queue items in the state variable.
   *
   * @param array $items
   *   An array which represents the queue of entities to manage.
   */
  public function setItems(array $items) {
    $this->app->stateSetArray('entity_inherit_queue', $items);
  }

}
