<?php

namespace Drupal\node_singles\Twig\Extension;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\node_singles\Service\NodeSinglesInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * A Twig extension for loading single nodes.
 */
class SingleExtension extends AbstractExtension {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The node singles service.
   *
   * @var \Drupal\node_singles\Service\NodeSinglesInterface
   */
  protected $singles;

  /**
   * Constructs the Twig extension.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\node_singles\Service\NodeSinglesInterface $singles
   *   The node singles service.
   */
  public function __construct(RendererInterface $renderer, NodeSinglesInterface $singles) {
    $this->renderer = $renderer;
    $this->singles = $singles;
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new TwigFunction('single', [$this, 'getSingle']),
    ];
  }

  /**
   * Returns a loaded single node by node type ID.
   */
  public function getSingle($bundle) {
    if (!is_string($bundle)) {
      return NULL;
    }

    $entity = $this->singles->getSingleByBundle($bundle);

    // Workaround to include caching metadata of the single entity.
    if ($entity instanceof EntityInterface) {
      $build = [];
      CacheableMetadata::createFromObject($entity)
        ->applyTo($build);
      $this->renderer->render($build);
    }

    return $entity;
  }

}
