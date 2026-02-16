<?php

namespace Drupal\flow\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;

/**
 * The interface implemented by all flow task plugins.
 */
interface FlowTaskInterface extends PluginInspectionInterface, DerivativeInspectionInterface, ConfigurableInterface, PluginWithSettingsInterface, ThirdPartySettingsInterface, FlowPluginInterface {

  /**
   * Operates the task on the given subject.
   *
   * @param \Drupal\flow\Plugin\FlowSubjectInterface $subject
   *   The subject.
   *
   * @throws \Drupal\flow\Exception\FlowException
   *   When something goes wrong and should be handled by Flow.
   *
   * @see \Drupal\flow\Exception
   *   For an overview of available exception categories, have a look at
   *   the exception classes that extend \Drupal\flow\Exception\FlowException.
   */
  public function operate(FlowSubjectInterface $subject): void;

}
