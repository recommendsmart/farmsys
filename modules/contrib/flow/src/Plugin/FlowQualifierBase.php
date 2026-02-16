<?php

namespace Drupal\flow\Plugin;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\flow\Helpers\FlowPluginTrait;

/**
 * Base class for Flow qualifier plugins.
 */
abstract class FlowQualifierBase extends PluginBase implements FlowQualifierInterface, ContainerFactoryPluginInterface, DependentPluginInterface {

  use FlowPluginTrait;

}
