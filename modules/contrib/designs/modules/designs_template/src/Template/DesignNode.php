<?php

namespace Drupal\designs_template\Template;

use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;

/**
 * Renders a design within the twig template.
 */
class DesignNode extends Node implements NodeOutputInterface {

  /**
   * DesignNode constructor.
   *
   * @param \Twig\Node\Expression\AbstractExpression $design
   *   The design name node.
   * @param \Twig\Node\Expression\AbstractExpression|null $settings
   *   The design settings.
   * @param \Twig\Node\Node|null $regions
   *   The design regions.
   * @param int $lineno
   *   The template line number.
   * @param string|null $tag
   *   The tag.
   */
  public function __construct(AbstractExpression $design, ?AbstractExpression $settings, ?Node $regions, int $lineno, string $tag = NULL) {
    $nodes = [
      'design' => $design,
    ];
    if (NULL !== $regions) {
      $nodes['regions'] = $regions;
    }
    if (NULL !== $settings) {
      $nodes['settings'] = $settings;
    }

    parent::__construct($nodes, [], $lineno, $tag);
  }

  /**
   * {@inheritdoc}
   */
  public function compile(Compiler $compiler) {
    $compiler->addDebugInfo($this);

    $template = 'design';

    $compiler
      ->write(sprintf("$%s = [\n", $template))
      ->indent()
      ->write("'#type' => 'design',\n")
      ->write("'#design' => ")
      ->subcompile($this->getNode('design'))
      ->raw(",\n")
      ->outdent()
      ->write("];\n");

    $this->addTarget($compiler, $template, 'settings');
    $this->addTarget($compiler, $template, 'regions');

    // Perform the rendering of the node.
    $contents = 'contents';
    $compiler
      ->write(sprintf("$%s = \\Drupal::service('renderer')->render($%s);\n", $contents, $template))
      ->write(sprintf("echo $%s;\n", $contents));
  }

  /**
   * Adds the values for the target.
   *
   * @param \Twig\Compiler $compiler
   *   The twig compiler.
   * @param string $varname
   *   The internal variable name.
   * @param string $target
   *   The target type.
   */
  protected function addTargetValues(Compiler $compiler, string $varname, string $target) {
    $compiler
      ->write(sprintf("$%s = ", $varname))
      ->raw('twig_to_array(')
      ->subcompile($this->getNode($target))
      ->raw(");\n");
  }

  /**
   * Generate the output to convert settings and regions into design.
   *
   * @param \Twig\Compiler $compiler
   *   The compiler.
   * @param string $template
   *   The template name.
   * @param string $target
   *   One of 'settings' and 'regions'.
   */
  protected function addTargetCopy(Compiler $compiler, string $template, string $target) {
    switch ($target) {
      case 'settings':
        $compiler
          ->write("if (\$value instanceof \Drupal\Core\Template\AttributeValueBase) {\n")
          ->indent()
          ->write("\$output = \$value->value();\n")
          ->write("if (\$value instanceof \Drupal\Core\Template\AttributeArray) \$output = implode(' ', \$output);\n")
          ->outdent()
          ->write("} else {\n")
          ->indent()
          ->write("\$output = (string)\$value;\n")
          ->outdent()
          ->write("}\n")
          ->write(sprintf("$%s['#configuration']['settings'][\$name] = ['plugin' => 'text', 'config' => ['value' => \$output]];\n", $template));
        break;

      case 'regions':
        $compiler
          ->write("switch (gettype(\$value)) {\n")
          ->indent()
          ->write(sprintf("case 'string': $%s[\$name] = ['#markup' => \Drupal\Core\Render\Markup::create(\$value)]; break;\n", $template))
          ->write(sprintf("case 'array': $%s[\$name] = ['#printed' => FALSE, '#children' => ''] + \$value; break;\n", $template))
          ->write(sprintf("case 'object': $%s[\$name] = ['#markup' => \$value]; break;\n", $template))
          ->write("case 'NULL': break;\n")
          ->write(sprintf("default: $%s[\$name] = \$value; break;\n", $template))
          ->outdent()
          ->write("}\n");
        break;
    }
  }

  /**
   * Generates the output for settings and regions.
   *
   * @param \Twig\Compiler $compiler
   *   The compiler.
   * @param string $template
   *   The template name.
   * @param string $target
   *   One of 'settings' or 'regions'.
   */
  protected function addTarget(Compiler $compiler, string $template, string $target) {
    if (!$this->hasNode($target)) {
      return;
    }

    $compiler
      ->write(sprintf("$%s = ", $target))
      ->raw('twig_to_array(')
      ->subcompile($this->getNode($target))
      ->raw(");\n")
      ->write(sprintf("foreach ($%s as \$name => \$value) {\n", $target))
      ->indent();

    $this->addTargetCopy($compiler, $template, $target);

    $compiler
      ->outdent()
      ->write("}\n");
  }

}
