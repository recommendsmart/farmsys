<?php

namespace Drupal\flow\Plugin;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\flow\Helpers\FlowPluginTrait;

/**
 * Base class for Flow subject plugins.
 */
abstract class FlowSubjectBase extends PluginBase implements FlowSubjectInterface, ContainerFactoryPluginInterface, DependentPluginInterface {

  use FlowPluginTrait;

}
