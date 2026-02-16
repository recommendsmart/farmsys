<?php

namespace Drupal\designs_template\Template;

use Twig\Compiler;
use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;

/**
 * Provides the region content node.
 */
class RegionUsingNode extends Node implements NodeOutputInterface {

  /**
   * RegionUsingNode constructor.
   *
   * @param string $name
   *   The region name.
   * @param string $source
   *   The region source.
   * @param int $lineno
   *   The template line number.
   * @param string|null $tag
   *   A tag.
   */
  public function __construct(string $name, string $source, int $lineno, string $tag = NULL) {
    parent::__construct([], ['name' => $name, 'source' => $source], $lineno, $tag);
  }

  /**
   * {@inheritdoc}
   */
  public function compile(Compiler $compiler) {
    $compiler
      ->raw('$context["')
      ->raw($this->getAttribute('name'))
      ->raw('"] ?? null');
  }

}
