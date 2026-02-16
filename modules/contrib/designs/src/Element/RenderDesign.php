<?php

namespace Drupal\designs\Element;

use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\designs\DesignSourceInterface;

/**
 * Provides a render element for the design.
 *
 * @RenderElement("design")
 */
class RenderDesign extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#pre_render' => [
        [static::class, 'preRenderDesign'],
      ],
      '#design' => '',
      '#design_source' => NULL,
      '#configuration' => [],
      '#context' => [],
    ];
  }

  /**
   * Renders a twig string directly.
   *
   * @param array $element
   *   The render element.
   *
   * @return array
   *   The render element.
   */
  public static function preRenderDesign(array $element) {
    /** @var \Drupal\designs\DesignManagerInterface $manager */
    $manager = \Drupal::service('plugin.manager.design');

    // Design without available template prints no markup.
    if (!$manager->hasDefinition($element['#design'])) {
      return $element + ['#markup' => ''];
    }

    // Get the configuration for the design.
    $configuration = $element['#configuration'] ?? [];

    // Ensure that when the regions are not defined they default to the same
    // name as the region.
    if (!isset($configuration['regions'])) {
      $definition = $manager->getDefinition($element['#design']);
      $configuration['regions'] = [];
      foreach ($definition->getRegionNames() as $name) {
        $configuration['regions'][$name] = [$name];
      }
    }

    // Get the design based on the configuration.
    $design = $manager->createInstance($element['#design'], $configuration);
    if (!empty($element['#design_source']) && $element['#design_source'] instanceof DesignSourceInterface) {
      $design->setSourcePlugin($element['#design_source']);
    }

    // Get the contents from the element.
    $contents = [];

    // Create the custom content elements.
    $custom = [];
    foreach ($design->getContents() as $content_id => $content) {
      $custom[$content_id] = $content->build($element);
    }

    // Process the settings.
    foreach ($design->getSettings() as $setting_id => $setting) {
      if (!isset($configuration['settings'][$setting_id]) && !empty($element[$setting_id])) {
        $contents[$setting_id] = $element[$setting_id];
      }
      else {
        $build = $setting->build($element);
        $contents[$setting_id] = $setting->process($build, $element);
      }
    }

    // Process the regions.
    foreach ($design->getRegions() as $region_id => $region) {
      $contents[$region_id] = $region->build($element, $custom);
    }

    // Content in regions that are not part of the design are considered
    // hidden regions and therefore should not display the content.
    $sources = [];
    foreach ($configuration['regions'] as $region_id => $content) {
      $sources[] = $region_id;
      if (isset($contents[$region_id])) {
        continue;
      }

      // Consider all elements printed for the purposes of rendering.
      foreach ($content as $child) {
        if (isset($element[$child])) {
          $sources[] = $child;
        }
      }
    }

    // Create the list of unused sources.
    $sources = array_merge($sources, $design->getUsedSources());
    foreach (Element::children($element) as $child) {
      if (!in_array($child, $sources)) {
        $contents['#unused'][] = $element[$child];
      }
    }

    return [
      '#theme' => 'design',
      '#design' => $design,
    ] + $contents;
  }

}
