<?php

namespace Drupal\block_breakpoint\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\layout_builder\LayoutBuilderEvents;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;

/**
 * Injects breakpoint configuration into the layout builder component.
 */
class BlockComponentRenderArray implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY] = ['onBuildRender'];
    return $events;
  }

  /**
   * Adds block_breakpoint render options to section component.
   *
   * @param \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent $event
   *   The section component render event.
   */
  public function onBuildRender(SectionComponentBuildRenderArrayEvent $event) {
    $build = $event->getBuild();
    if (!empty($build)) {
      $build['#block_breakpoint'] = $event->getComponent()->getThirdPartySettings('block_breakpoint');
      $event->setBuild($build);
    }
  }

}
