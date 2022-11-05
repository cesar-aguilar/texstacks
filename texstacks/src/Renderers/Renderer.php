<?php

namespace TexStacks\Renderers;

use TexStacks\Renderers\CaptionRenderer;
use TexStacks\Renderers\EnvironmentRenderer;
use TexStacks\Renderers\SectionCommandRenderer;
use TexStacks\Renderers\ThmEnvironmentRenderer;
use TexStacks\Renderers\ListEnvironmentRenderer;
use TexStacks\Renderers\GroupEnvironmentRenderer;
use TexStacks\Renderers\TabularEnvironmentRenderer;
use TexStacks\Renderers\DisplayMathEnvironmentRenderer;

class Renderer
{
  public static function render($root): string
  {
    return self::renderRecursively($root);
  }

  private static function renderRecursively($node): string
  {
    if ($node->isLeaf()) {
      return self::renderNode($node, $node->body);
    }

    return self::renderNode(
      $node,
      implode('', array_map(fn ($child) => self::renderRecursively($child), $node->children()))
    );
  }

  private static function renderNode($node, string $body = null): string
  {

    $body = $body ?? '';

    if ($node->hasType('root')) return self::renderRoot($node, $body);

    if ($node->hasType('section-cmd')) return SectionCommandRenderer::renderNode($node, $body);

    if ($node->hasType('displaymath-environment')) return DisplayMathEnvironmentRenderer::renderNode($node, $body);

    if ($node->hasType('inlinemath')) return "\\(" . $body . "\\)";

    if ($node->hasType('thm-environment')) return ThmEnvironmentRenderer::renderNode($node, $body);

    if ($node->hasType('group-environment')) return GroupEnvironmentRenderer::renderNode($node, $body);

    if ($node->hasType('environment')) return EnvironmentRenderer::renderNode($node, $body);

    if ($node->hasType('tabular-environment')) return TabularEnvironmentRenderer::renderNode($node, $body);

    if ($node->hasType('list-environment')) return ListEnvironmentRenderer::renderNode($node, $body);

    if ($node->hasType('bibliography-environment')) return BibliographyEnvironmentRenderer::renderNode($node, $body);

    if ($node->hasType('font-cmd')) return FontCommandRenderer::renderNode($node, $body);

    if ($node->hasType(['symbol', 'alpha-symbol'])) return SymbolCommandRenderer::renderNode($node, $body);

    if ($node->hasType('caption')) return CaptionRenderer::renderNode($node, $body);

    if ($node->hasType('two-args-cmd')) return self::renderTwoArgsCommand($node, $body);

    if ($node->hasType('verbatim-environment')) return "<pre>$body</pre>";

    if ($node->hasType('spacing-cmd')) return self::renderSpacingCommand($node, $body);

    if ($node->hasType('item')) return self::renderItemNode($node, $body);

    if ($node->hasType('bibitem')) return self::renderBibItemNode($node, $body);

    if ($node->hasType('includegraphics')) return self::renderIncludeGraphics($node, $body);

    if ($node->hasType('label')) return $node->commandSource();

    if ($node->hasType('ref')) return self::renderRef($node, $body);

    if ($node->hasType('eqref')) return self::renderEqref($node, $body);

    if ($node->hasType('cite')) return self::renderCitations($node, $body);

    if ($node->hasType('tag')) return "\\tag{" . $body . "}";

    if ($node->hasType('font-declaration')) return self::renderFontDeclaration($node);

    if ($node->ancestorOfType(['displaymath-environment', 'inlinemath', 'tabular-environment', 'verbatim-environment'])) return $body;

    // Remove vertical spacing of the type \\[1em] since not in tabular-like environment
    // $output = preg_replace('/(\\\)(\\\)\[(.*?)\]/', '<br>', $body);

    return preg_replace('/\n{2,}/', "<br><br>", $body);

    // Replace two \n characters with <br>
    // return str_replace("\n\n", '<br><br>', $output);

    // Remove double backslashes (the node is text and should not be in math or tabular environment)
    // return preg_replace('/(\\\)(\\\)/', '<br>', $output);
  }

  private static function renderRoot($node, $body)
  {
    if ($node->hasClasses()) {
      $classes = $node->getClasses();
      return "<span class='$classes'>$body</span>";
    }
    return $body;
  }

  private static function renderItemNode($node, $body)
  {
    if ($node->ancestorOfType('verbatim-environment')) return $node->commandSource() . ' ' . $body;

    return "<li>$body</li>";
  }

  private static function renderBibItemNode($node, $body)
  {
    if ($node->ancestorOfType('verbatim-environment')) return $node->commandSource() . ' ' . $body;

    $body = str_replace('<br>', '', $body);

    return "<li id=\"{$node->commandContent()}\">$body</li>";
  }

  private static function renderFontDeclaration($node)
  {
    if ($node->ancestorOfType('verbatim-environment')) return "\\" . $node->body;

    return '';
  }

  private static function renderIncludeGraphics($node, string $body = null): string
  {

    if ($node->ancestorOfType('verbatim-environment')) return $node->commandSource();

    return "<img src=\"{$node->commandContent()}\" alt=\"{$node->commandContent()}\" />";
  }

  private static function renderCitations($node, string $body = null): string
  {

    $options = $node->commandOptions() != '' ? ", " . $node->commandOptions() : null;

    $ids = array_map(trim(...), explode(',', $node->commandContent()));

    $nums = explode(',', $node->body);

    foreach (array_combine($ids, $nums) as $id => $num) {
      $a[] = "<a href=\"#$id\">$num</a>";
    }

    $value = implode(', ', $a);

    return " [$value$options]";
  }

  private static function renderRef($node, string $body = null): string
  {
    return "<a href='#{$node->commandContent()}'>{$node->commandOptions()}</a>";
  }

  private static function renderEqref($node, string $body = null): string
  {
    return "(<a style=\"margin:0 0.1rem;\" href='#{$node->commandContent()}'>{$node->commandOptions()}</a>)";
  }

  private static function renderSpacingCommand($node, string $body = null): string
  {
    if ($node->ancestorOfType(['verbatim-environment', 'displaymath-environment', 'inlinemath'])) return $node->commandSource() . ' ';

    return '';
  }

  private static function renderTwoArgsCommand($node, string $body = null): string
  {
    if ($node->ancestorOfType(['verbatim-environment', 'displaymath-environment', 'inlinemath'])) return $node->commandSource();

    if ($node->commandName() === 'texorpdfstring') {
      return Renderer::render($node->commandContent());
    }

    return $node->commandSource();
  }
}
