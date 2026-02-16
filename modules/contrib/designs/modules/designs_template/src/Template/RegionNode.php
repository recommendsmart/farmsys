<?php

namespace Drupal\designs_template\Template;

use Twig\Node\BlockNode;
use Twig\Node\Node;

/**
 * Provides region as a node.
 */
class RegionNode extends BlockNode {

  /**
   * RegionNode constructor.
   *
   * @param string $name
   *   The name of the region.
   * @param string $source
   *   The name of the source content.
   * @param \Twig\Node\Node $body
   *   The body of the region.
   * @param int $lineno
   *   The line number.
   * @param string|null $tag
   *   A tag.
   */
  public function __construct(string $name, string $source, Node $body, int $lineno, string $tag = NULL) {
    parent::__construct($name, $body, $lineno, $tag);
    $this->setAttribute('source', $source);
  }

}
