<?php

namespace Drupal\flow\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;

/**
 * The interface implemented by all flow subject plugins.
 */
interface FlowSubjectInterface extends PluginInspectionInterface, DerivativeInspectionInterface, ConfigurableInterface, PluginWithSettingsInterface, ThirdPartySettingsInterface, FlowPluginInterface {

  /**
   * Get the subject items that are being identified by this plugin.
   *
   * As the Flow module is solely built around content,
   * a subject item is always a content entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   An iterable data type that allows for traversing on all identified items.
   *   This may be a simple array that holds one or multiple items, but it may
   *   also be a generator that allows traversing on a large amount of items.
   */
  public function getSubjectItems(): iterable;

}
