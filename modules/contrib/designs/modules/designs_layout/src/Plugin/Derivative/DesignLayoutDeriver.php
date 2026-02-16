<?php

namespace Drupal\designs_layout\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

use Drupal\Core\Layout\LayoutDefinition;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\designs\DesignDefinition;
use Drupal\designs\DesignManagerInterface;
use Drupal\designs_layout\Plugin\Layout\DesignLayout;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the designs as layouts.
 */
class DesignLayoutDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The design manager.
   *
   * @var \Drupal\designs\DesignManagerInterface
   */
  protected $designManager;

  /**
   * DesignLayoutDeriver constructor.
   *
   * @param \Drupal\designs\DesignManagerInterface $designManager
   *   The design manager.
   */
  public function __construct(DesignManagerInterface $designManager) {
    $this->designManager = $designManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.design')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $derivatives = [];

    /** @var \Drupal\designs\DesignDefinition $definition */
    foreach ($this->designManager->getDefinitions() as $definition) {
      $layout_definition = $this->getDefinition($base_plugin_definition, $definition);
      $derivatives[$definition->getTemplateId()] = $layout_definition;
    }

    return $derivatives;
  }

  /**
   * Get the derivative plugin definition.
   *
   * @param \Drupal\Core\Layout\LayoutDefinition $base_plugin_definition
   *   The original plugin definition.
   * @param \Drupal\designs\DesignDefinition $definition
   *   The design definition.
   *
   * @return \Drupal\Core\Layout\LayoutDefinition
   *   The design derivative definition.
   */
  protected function getDefinition(LayoutDefinition $base_plugin_definition, DesignDefinition $definition) {
    $config = [
      'label' => $definition->getLabel(),
      'provider' => $definition->getProvider(),
      'category' => $definition->getCategory(),
      'class' => DesignLayout::class,
      'design' => $definition->id(),
    ];

    foreach ($definition->getRegions() as $region_id => $region) {
      $config['regions'][$region_id]['label'] = $region['label'];
    }

    return new LayoutDefinition($config);
  }

}
