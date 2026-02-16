<?php

namespace Drupal\book_pdf\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a block which outputs a link to print to PDF.
 *
 * @Block(
 *   id = "book_pdf_link",
 *   admin_label = @Translation("Book PDF: link"),
 *   category = @Translation("Book"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", required = TRUE),
 *   }
 * )
 */
class BookPdfLinkBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    if (!$node || !isset($node->book)) {
      return [];
    }
    $build['print_link'] = [
      '#type' => 'link',
      '#url' => Url::fromRoute('book_pdf.send', ['book' => $node->book['bid']]),
      '#title' => 'Download as PDF',
    ];
    return $build;
  }

}
