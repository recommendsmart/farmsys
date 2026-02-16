<?php

namespace Drupal\flow\Plugin;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\flow\Helpers\FlowPluginTrait;

/**
 * Base class for Flow task plugins.
 */
abstract class FlowTaskBase extends PluginBase implements FlowTaskInterface, ContainerFactoryPluginInterface, DependentPluginInterface {

  use FlowPluginTrait;

}
