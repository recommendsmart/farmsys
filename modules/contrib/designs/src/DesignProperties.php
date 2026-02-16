<?php

namespace Drupal\designs;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Helper class used for data type properties.
 */
class DesignProperties implements DesignPropertiesInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * DesignProperties constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RendererInterface $renderer) {
    $this->entityTypeManager = $entityTypeManager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function getMarkup($value) {
    if (is_null($value)) {
      return ['#markup' => ''];
    }
    elseif (is_scalar($value)) {
      return [
        '#markup' => $value,
      ];
    }
    elseif (method_exists($value, 'toString')) {
      return [
        '#markup' => $value->toString(),
      ];
    }
    elseif (method_exists($value, '__toString')) {
      return [
        '#markup' => (string) $value,
      ];
    }
    elseif ($value instanceof ContentEntityBase) {
      $view_builder = $this->entityTypeManager->getViewBuilder($value->getEntityTypeId());
      $view = $view_builder->view($value);
      return [
        '#markup' => $this->renderer->render($view),
      ];
    }
    return [];
  }

}
