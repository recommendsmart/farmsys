<?php

namespace Drupal\designs_layout\Plugin\designs\source;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\designs\DesignManagerInterface;
use Drupal\designs\DesignSourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The source using layout.
 *
 * @DesignSource(
 *   id = "layout",
 *   label = @Translation("Layout"),
 *   usesCustomContent = FALSE,
 *   usesRegionsForm = FALSE
 * )
 */
class LayoutSource extends DesignSourceBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The design manager.
   *
   * @var \Drupal\designs\DesignManagerInterface
   */
  protected $designManager;

  /**
   * LayoutSource constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin identifier.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\designs\DesignManagerInterface $designManager
   *   The design manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, DesignManagerInterface $designManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->designManager = $designManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.design')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSources() {
    if (!$this->designManager->hasDefinition($this->configuration['design'])) {
      return [];
    }
    $definition = $this->designManager->getDefinition($this->configuration['design']);
    return $definition->getRegionLabels();
  }

}
