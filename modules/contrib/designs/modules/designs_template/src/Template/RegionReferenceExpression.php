<?php

namespace Drupal\designs_template\Template;

use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;

/**
 * Provides a reference to a region.
 */
class RegionReferenceExpression extends AbstractExpression {

  /**
   * RegionReferenceExpression constructor.
   *
   * @param \Twig\Node\Node $body
   *   The body of the region.
   */
  public function __construct(Node $body) {
    $nodes = [
      'body' => $body,
    ];
    parent::__construct($nodes, [], $body->getTemplateLine(), $body->getNodeTag());
  }

}
