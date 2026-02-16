<?php

namespace Drupal\designs_view;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\designs\DesignManagerInterface;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\ViewExecutable;

/**
 * Provides support for views designs.
 */
class ViewsHandler implements TrustedCallbackInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The design manager.
   *
   * @var \Drupal\designs\DesignManagerInterface
   */
  protected $designManager;

  /**
   * ViewsHandler constructor.
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
  public static function trustedCallbacks() {
    return ['preRenderStyle', 'preRenderAreas'];
  }

  /**
   * Processes the view after all the rendering has taken place.
   *
   * @param array $element
   *   The render element.
   *
   * @return array
   *   The render element.
   */
  public function preRenderStyle(array $element) {
    // Load the build design.
    $config = $element['#rows']['#design'];
    $design = $this->designManager->createSourcedInstance(
      $config['design'],
      $config,
      'views_style',
      []
    );

    // There is no valid design so just render as normal.
    if (!$design) {
      return $element;
    }

    $build = $design->build($element);
    $element['build'] = $build;
    unset($element['#theme']);

    return $element;
  }

  /**
   * Process each of the view areas to apply any design styles.
   *
   * @param array $element
   *   The render element.
   *
   * @return array
   *   The modified render element.
   */
  public function preRenderAreas(array $element) {
    $options = $element['#design'];
    foreach (['header', 'footer', 'empty', 'pager'] as $type) {
      if (empty($options[$type]['design']) || empty($element["#{$type}"])) {
        continue;
      }
      $display_options = $element['#view']->display_handler->getOption($type);

      // Build the design based on the configuration.
      $design = $this->designManager->createSourcedInstance(
        $options[$type]['design'],
        $options[$type],
        $this->getSource($type),
        $this->getSources($type, $display_options),
      );
      if ($design) {
        $element["#{$type}"]['#view'] = $element['#view'];
        $element["#{$type}"] = $design->build($element["#{$type}"]);
      }
    }
    return $element;
  }

  /**
   * Implements hook_views_post_render().
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view.
   * @param array|string $output
   *   The output of the view.
   * @param \Drupal\views\Plugin\views\cache\CachePluginBase $cache
   *   The views cache.
   */
  public function postRender(ViewExecutable $view, &$output, CachePluginBase $cache) {
    // Apply area design rendering where appropriate.
    $options = $view->display_handler->getOption('design');
    foreach (['header', 'footer', 'empty', 'pager'] as $area) {
      if (!empty($options[$area]['design'])) {
        $output['#design'] = $options;
        $output['#pre_render'][] = [$this, 'preRenderAreas'];
        break;
      }
    }

    // Apply style-based behaviour on rows.
    if (!empty($output['#rows']['#design'])) {
      $output['#pre_render'][] = [$this, 'preRenderStyle'];
    }
  }

  /**
   * Get the source from the type.
   *
   * @param string $type
   *   The views handler type.
   *
   * @return string
   *   The source plugin identifier.
   */
  protected function getSource($type) {
    return $type === 'pager' ? 'views_pager' : 'views_area';
  }

  /**
   * Get the source configuration for the specific views area.
   *
   * @param string $type
   *   The type.
   * @param array $options
   *   The options.
   *
   * @return array
   *   The source configuration.
   */
  protected function getSources($type, array $options) {
    return $type === 'pager' ? $options : [];
  }

}
