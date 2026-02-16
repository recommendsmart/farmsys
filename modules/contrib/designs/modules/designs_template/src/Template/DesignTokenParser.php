<?php

namespace Drupal\designs_template\Template;

use Drupal\Core\Render\RendererInterface;
use Drupal\designs\DesignManagerInterface;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\Node\TextNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Embeds a design.
 */
final class DesignTokenParser extends AbstractTokenParser {

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
   * DesignTokenParser constructor.
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
  public function getTag() {
    return 'design';
  }

  /**
   * {@inheritdoc}
   */
  public function parse(Token $token) {
    $lineno = $token->getLine();
    $stream = $this->parser->getStream();

    // The name of the design.
    $parent = $this->parser->getExpressionParser()->parseExpression();

    // The settings used for the design.
    $settings = $this->parseSettings();

    $this->parser->pushLocalScope();
    $body = $this->parser->subparse([$this, 'decideDesignEnd'], TRUE);
    $this->parser->popLocalScope();
    $stream->expect(/* Token::BLOCK_END_TYPE */ 3);

    // The body should be either all regions or have only one region definition
    // for the design.
    $syntax_error = FALSE;
    $regions = [];

    // Sort body into regions and content.
    if (get_class($body) === Node::class) {
      foreach ($body as $node) {
        if ($node instanceof RegionReferenceNode || $node instanceof RegionUsingNode) {
          $regions[] = $node;
          continue;
        }
        if ($node instanceof TextNode && ctype_space($node->getAttribute('data'))) {
          continue;
        }
        $syntax_error = TRUE;
      }
    }
    elseif ($body instanceof RegionReferenceNode || $body instanceof RegionUsingNode) {
      $regions[] = $body;
    }
    elseif (!$body instanceof TextNode && ctype_space($body->getAttribute('data'))) {
      $syntax_error = TRUE;
    }

    // Has both regions and content.
    if ($syntax_error) {
      throw new SyntaxError('Design can only contain region definitions.');
    }

    // Create the array expression for the regions.
    $expr = new ArrayExpression([], $stream->getCurrent()->getLine());
    foreach ($regions as $region) {
      $key = new ConstantExpression(
        $region->getAttribute('source'),
        $region->getTemplateLine()
      );
      $value = new RegionReferenceExpression($region);
      $expr->addElement($value, $key);
    }

    // Create the design node, and add blocks to it.
    return new DesignNode($parent, $settings, $expr, $lineno);
  }

  /**
   * Check end of design.
   *
   * @param \Twig\Token $token
   *   The current token.
   *
   * @return bool
   *   The result.
   */
  public function decideDesignEnd(Token $token) {
    return $token->test('enddesign');
  }

  /**
   * Get the settings used for the design.
   *
   * @return \Twig\Node\Expression\AbstractExpression|null
   *   The settings or nothing.
   */
  protected function parseSettings() {
    $stream = $this->parser->getStream();

    $variables = NULL;
    if ($stream->nextIf(/* Token::NAME_TYPE */ 5, 'with')) {
      $variables = $this->parser->getExpressionParser()->parseExpression();
    }

    $stream->expect(/* Token::BLOCK_END_TYPE */ 3);

    return $variables;
  }

}
