<?php

namespace Drupal\flow\Helpers;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Trait for common functions of Flow-related plugins.
 */
trait FlowPluginTrait {

  use ConfigurablePluginTrait;
  use DependencySerializationTrait;
  use PluginWithSettingsTrait;
  use ThirdPartySettingsTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
    if (!isset($this->configuration['settings'])) {
      $this->configuration['settings'] = [];
    }
    if (!isset($this->configuration['third_party_settings'])) {
      $this->configuration['third_party_settings'] = [];
    }
    $this->settings = &$this->configuration['settings'];
    $this->thirdPartySettings = &$this->configuration['third_party_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId(): string {
    $definition = $this->getPluginDefinition();
    return $definition['entity_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType(): EntityTypeInterface {
    $definition = $this->getPluginDefinition();
    $etm = \Drupal::entityTypeManager();
    return $etm->getDefinition($definition['entity_type']);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    $definition = $this->getPluginDefinition();
    return $definition['bundle'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityBundleConfig(): ?ConfigEntityInterface {
    $definition = $this->getPluginDefinition();
    $entity_type = $this->getEntityType();
    if ($bundle_entity_type_id = $entity_type->getBundleEntityType()) {
      $etm = \Drupal::entityTypeManager();
      return $etm->getStorage($bundle_entity_type_id)->load($definition['bundle']);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = [];
    if ($bundle_config = $this->getEntityBundleConfig()) {
      $dependencies[$bundle_config->getConfigDependencyKey()][] = $bundle_config->getConfigDependencyName();
    }
    return $dependencies;
  }

}
