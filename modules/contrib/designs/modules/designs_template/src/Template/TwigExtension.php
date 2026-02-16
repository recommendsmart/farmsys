<?php

namespace Drupal\designs_template\Template;

use Drupal\Core\Render\RendererInterface;
use Drupal\designs\DesignManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides a twig extension for using designs within templates.
 */
class TwigExtension extends AbstractExtension {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The design manager.
   *
   * @var \Drupal\designs\DesignManagerInterface
   */
  protected $designManager;

  /**
   * TwigExtension constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\designs\DesignManagerInterface $designManager
   *   The design manager.
   */
  public function __construct(
    RendererInterface $renderer,
    DesignManagerInterface $designManager
  ) {
    $this->renderer = $renderer;
    $this->designManager = $designManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'designs';
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenParsers() {
    return [
      new DesignTokenParser($this->renderer, $this->designManager),
      new RegionTokenParser(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new TwigFilter('integer', function ($a) {
        return intval($a);
      }),
      new TwigFilter('string', function ($a) {
        return (string) $a;
      }),
      new TwigFilter('unique', function ($a) {
        return array_unique((array) $a);
      }),
    ];
  }

}
