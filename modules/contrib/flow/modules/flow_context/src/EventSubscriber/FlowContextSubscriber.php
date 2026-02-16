<?php

namespace Drupal\flow_context\EventSubscriber;

use Drupal\context_stack\ContextStackInterface;
use Drupal\context_stack\Plugin\Context\GenericEntityContext;
use Drupal\flow\Event\FlowBeginEvent;
use Drupal\flow\Event\FlowEndEvent;
use Drupal\flow\FlowEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to Flow-related events for adding entities to the Context Stack.
 */
class FlowContextSubscriber implements EventSubscriberInterface {

  /**
   * The "flow" context stack.
   *
   * @var \Drupal\context_stack\ContextStackInterface
   */
  protected ContextStackInterface $contextStack;

  /**
   * Constructs a new FlowContextSubscriber instance.
   *
   * @param \Drupal\context_stack\ContextStackInterface $context_stack
   *   The "flow" context stack.
   */
  public function __construct(ContextStackInterface $context_stack) {
    $this->contextStack = $context_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[FlowEvents::BEGIN][] = ['onBegin'];
    $events[FlowEvents::END][] = ['onEnd'];
    return $events;
  }

  /**
   * Subscriber method before configured flow begins to be applied.
   *
   * @param \Drupal\flow\Event\FlowBeginEvent $event
   *   The according event object.
   */
  public function onBegin(FlowBeginEvent $event): void {
    $entity = $event->getEntity();
    $context_id = $entity->getEntityTypeId();
    $context_label = $event->getTaskMode();
    $context = GenericEntityContext::fromEntity($entity, $context_label);
    $this->contextStack->addContext($context, $context_id);
  }

  /**
   * Subscriber method after configured flow got applied.
   *
   * @param \Drupal\flow\Event\FlowEndEvent $event
   *   The according event object.
   */
  public function onEnd(FlowEndEvent $event): void {
    $entity = $event->getEntity();
    $context_id = $entity->getEntityTypeId();
    $context_label = $event->getTaskMode();
    while ($collection = $this->contextStack->pop()) {
      if (!$collection->hasContext($context_id)) {
        continue;
      }
      $context = $collection->getContext($context_id);
      if ((($context->getContextValue() === $entity) || ($entity->uuid() && ($context->getContextValue()->uuid() === $entity->uuid()))) && ($context_label === $context->getContextDefinition()->getLabel())) {
        break;
      }
    }
  }

}
