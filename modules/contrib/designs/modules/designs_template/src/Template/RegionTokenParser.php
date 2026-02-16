<?php

namespace Drupal\designs_template\Template;

use Twig\Error\SyntaxError;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Parsers a design region.
 */
final class RegionTokenParser extends AbstractTokenParser {

  /**
   * {@inheritdoc}
   */
  public function getTag() {
    return 'region';
  }

  /**
   * {@inheritdoc}
   */
  public function parse(Token $token) {
    $lineno = $token->getLine();

    $stream = $this->parser->getStream();

    $name = $stream->expect(/* Token::NAME_TYPE */ 5)->getValue();

    // Allows region to simplify to {% region name using variable %}.
    $variable = NULL;
    if ($stream->nextIf(/* Token::NAME_TYPE */ 5, 'using')) {
      $variable = $stream->expect(/* Token::NAME_TYPE */ 5)->getValue();
    }

    $stream->expect(/* Token::BLOCK_END_TYPE */ 3);

    // A special reference using node behaviour.
    if ($variable) {
      return new RegionUsingNode($variable, $name, $lineno, $this->getTag());
    }

    $reference = $this->parser->getVarName();

    $this->parser->setBlock($reference, $region = new RegionNode($reference, $name, new Node([]), $lineno, $this->getTag()));
    $this->parser->pushLocalScope();
    $this->parser->pushBlockStack($reference);

    $body = $this->parser->subparse([$this, 'decodeRegionEnd'], TRUE);

    if ($token = $stream->nextIf(/* Token::NAME_TYPE */ 5)) {
      $value = $token->getValue();

      if ($value != $name) {
        throw new SyntaxError(sprintf('Expected endregion for region "%s" (but "%s" given).', $name, $value), $stream->getCurrent()->getLine(), $stream->getSourceContext());
      }
    }

    $stream->expect(/* Token::BLOCK_END_TYPE */ 3);

    $region->setNode('body', $body);
    $this->parser->popBlockStack();
    $this->parser->popLocalScope();

    // Create the design node, and add blocks to it.
    return new RegionReferenceNode($reference, $name, $lineno, $this->getTag());
  }

  /**
   * Check end of region.
   *
   * @param \Twig\Token $token
   *   The current token.
   *
   * @return bool
   *   The result.
   */
  public function decodeRegionEnd(Token $token) {
    return $token->test('endregion');
  }

}
