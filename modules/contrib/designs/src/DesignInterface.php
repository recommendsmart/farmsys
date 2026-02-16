<?php

namespace Drupal\designs;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an interface for Design plugins.
 */
interface DesignInterface extends PluginInspectionInterface, DerivativeInspectionInterface, ConfigurableInterface, DependentPluginInterface, PluginFormInterface {

  /**
   * Build the design render array for an element.
   *
   * @param array $element
   *   The render element.
   *
   * @return array
   *   Render array for the design.
   */
  public function build(array &$element);

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\designs\DesignDefinition
   *   The design definition.
   */
  public function getPluginDefinition();

  /**
   * Get the design source plugin.
   *
   * @return \Drupal\designs\DesignSourceInterface
   *   The design source.
   */
  public function getSourcePlugin();

  /**
   * Set the design source plugin.
   *
   * @param \Drupal\designs\DesignSourceInterface $source
   *   The design source plugin.
   *
   * @return $this
   *   The object instance.
   */
  public function setSourcePlugin(DesignSourceInterface $source);

  /**
   * Get the settings for the design.
   *
   * @return \Drupal\designs\DesignSettingInterface[]
   *   The settings.
   */
  public function getSettings();

  /**
   * Get setting by the identifier.
   *
   * @param string $setting_id
   *   The setting identifier.
   *
   * @return \Drupal\designs\DesignSettingInterface|null
   *   The design setting.
   */
  public function getSetting(string $setting_id): ?DesignSettingInterface;

  /**
   * Get the extra content for the design.
   *
   * @return \Drupal\designs\DesignContentInterface[]
   *   The content.
   */
  public function getContents();

  /**
   * Get custom content by identifier.
   *
   * @param string $content_id
   *   The content identifier.
   *
   * @return \Drupal\designs\DesignContentInterface|null
   *   The design custom content.
   */
  public function getContent(string $content_id): ?DesignContentInterface;

  /**
   * Get the source labels from the source plugin and custom content.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   The source labels.
   */
  public function getSources();

  /**
   * Get the used source keys.
   *
   * @return string[]
   *   The used sources by key.
   */
  public function getUsedSources();

  /**
   * Get the design regions.
   *
   * @return \Drupal\designs\DesignRegion[]
   *   The regions.
   */
  public function getRegions();

  /**
   * Set the configuration design in the default state.
   *
   * This keeps existing settings and custom content, but initializes the
   * regions with the default content defined by the design source.
   */
  public function setDefaultDesign();

}
