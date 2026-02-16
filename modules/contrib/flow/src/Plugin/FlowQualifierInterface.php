<?php

namespace Drupal\flow\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * The interface implemented by all flow qualifier plugins.
 */
interface FlowQualifierInterface extends PluginInspectionInterface, DerivativeInspectionInterface, ConfigurableInterface, PluginWithSettingsInterface, ThirdPartySettingsInterface, FlowPluginInterface {

  /**
   * Evaluates whether the given entity is qualified.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity that is to be evaluated for qualification.
   *
   * @return bool
   *   Returns TRUE if the entity is qualified, FALSE otherwise.
   */
  public function qualifies(ContentEntityInterface $entity): bool;

}
