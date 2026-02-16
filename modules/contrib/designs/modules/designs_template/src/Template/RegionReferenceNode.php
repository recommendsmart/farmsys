<?php

namespace Drupal\designs_template\Template;

use Twig\Compiler;
use Twig\Node\BlockReferenceNode;

/**
 * Provides the region reference node.
 */
class RegionReferenceNode extends BlockReferenceNode {

  /**
   * RegionReferenceNode constructor.
   *
   * @param string $name
   *   The name of the region.
   * @param string $source
   *   The source of the region.
   * @param int $lineno
   *   The line number.
   * @param string|null $tag
   *   A tag.
   */
  public function __construct(string $name, string $source, int $lineno, string $tag = NULL) {
    parent::__construct($name, $lineno, $tag);
    $this->setAttribute('source', $source);
  }

  /**
   * {@inheritdoc}
   */
  public function compile(Compiler $compiler) {
    $compiler
      ->addDebugInfo($this)
      ->write(sprintf("\$this->renderBlock('%s', \$context, \$blocks)\n", $this->getAttribute('name')));
  }

}
