<?php

declare(strict_types = 1);

namespace Drupal\entity_version_workflows_example\EventSubscriber;

use Drupal\Core\State\State;
use Drupal\entity_version_workflows\Event\CheckEntityChangedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Test event subscriber for the entity changed blacklisted fields.
 */
class TestCheckEntityChangedSubscriber implements EventSubscriberInterface {

  const STATE = 'entity_version.test_skip_title_on';

  /**
   * The state.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * TestCheckEntityChangedSubscriber constructor.
   *
   * @param \Drupal\Core\State\State $state
   *   The state.
   */
  public function __construct(State $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      CheckEntityChangedEvent::EVENT => 'skipTitle',
    ];
  }

  /**
   * Skips the node title from when checking for entity changes.
   *
   * @param \Drupal\entity_version_workflows\Event\CheckEntityChangedEvent $event
   *   The event.
   */
  public function skipTitle(CheckEntityChangedEvent $event): void {
    if ($this->state->get(static::STATE) !== TRUE) {
      return;
    }

    $field_blacklist = $event->getFieldBlacklist();
    $field_blacklist[] = 'title';
    $event->setFieldBlacklist($field_blacklist);
  }

}
