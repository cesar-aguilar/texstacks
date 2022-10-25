<?php

namespace TexStacks\Renderers;

use TexStacks\Renderers\EnvironmentRenderer;
use TexStacks\Renderers\GroupEnvironmentRenderer;
use TexStacks\Renderers\SectionCommandRenderer;
use TexStacks\Renderers\ThmEnvironmentRenderer;
use TexStacks\Renderers\ListEnvironmentRenderer;
use TexStacks\Renderers\TabularEnvironmentRenderer;
use TexStacks\Renderers\DisplayMathEnvironmentRenderer;

class Renderer
{
  public function renderTree($root): string
  {
    return $this->renderRecursively($root);
  }

  private function renderRecursively($node): string
  {
    if ($node->isLeaf()) {
      return $this->renderNode($node, $node->body);
    }

    return $this->renderNode(
      $node,
      implode('', array_map(fn ($child) => $this->renderRecursively($child), $node->children()))
    );
  }

  private function renderNode($node, string $body = null): string
  {

    if ($node->type === 'section-cmd') return SectionCommandRenderer::renderNode($node, $body);
    
    if ($node->type === 'displaymath-environment') return DisplayMathEnvironmentRenderer::renderNode($node, $body);

    if ($node->type === 'inlinemath') return "\\(" . $body . "\\)";

    if ($node->type === 'thm-environment') return ThmEnvironmentRenderer::renderNode($node, $body);

    if ($node->type === 'group-environment') return GroupEnvironmentRenderer::renderNode($node, $body);

    if ($node->type === 'environment') return EnvironmentRenderer::renderNode($node, $body);

    if ($node->type === 'tabular-environment') return TabularEnvironmentRenderer::renderNode($node, $body);

    if ($node->type === 'list-environment') return ListEnvironmentRenderer::renderNode($node, $body);

    if ($node->type === 'bibliography-environment') return BibliographyEnvironmentRenderer::renderNode($node, $body);

    if ($node->type === 'verbatim-environment') return "<pre>$body</pre>";

    // if ($node->type === 'font-cmd') return FontCommandRenderer::renderNode($node, $body);

    if ($node->type === 'symbol') return SymbolCommandRenderer::renderNode($node, $body);

    if ($node->type === 'item') return self::renderItemNode($node, $body);

    if ($node->type === 'bibitem') return self::renderBibItemNode($node, $body);

    if ($node->type === 'includegraphics') return self::renderIncludeGraphics($node, $body);

    if ($node->type === 'caption') return self::renderCaptionEnvironment($node, $body);

    if ($node->type === 'label') return $node->commandSource();

    if ($node->type === 'ref') return "<a href='#{$node->commandContent()}'>{$node->commandOptions()}</a>";

    if ($node->type === 'eqref') return "( <a href='#{$node->commandContent()}'>{$node->commandOptions()}</a> )";

    if ($node->type === 'cite') return self::renderCitations($node, $body);

    if ($node->type === 'font-declaration') return self::renderFontDeclaration($node);

    if ($node->ancestorOfType(['displaymath-environment', 'inlinemath', 'tabular-environment', 'verbatim-environment'])) return $body;

    // Remove vertical spacing of the type \\[1em] since not in tabular-like environment
    $output = preg_replace('/(\\\)(\\\)\[(.*?)\]/', '<br>', $body);

    $output = preg_replace('/\n{3,}/', "<br><br>", $output);

    // Replace two \n characters with <br>
    // $output = str_replace("\n\n", '<br><br>', $output);

    // Remove double backslashes (the node is text and should not be in math or tabular environment)
    return preg_replace('/(\\\)(\\\)/', '<br>', $output);

  }

  private function renderItemNode($node, $body)
  {
    if ($node->ancestorOfType('verbatim-environment')) return $node->commandSource() . ' ' . $body;

    return "<li>$body</li>";
  }

  private function renderBibItemNode($node, $body)
  {
    if ($node->ancestorOfType('verbatim-environment')) return $node->commandSource() . ' ' . $body;

    return "<li id=\"{$node->commandContent()}\">$body</li>";
  }

  private function renderFontDeclaration($node)
  {
    if ($node->ancestorOfType('verbatim-environment')) return "\\" . $node->body;

    return '';
  }
  
  private static function renderIncludeGraphics($node, string $body = null): string
  {

    if ($node->ancestorOfType('verbatim-environment')) return $node->commandSource();

    return "<img src=\"{$node->commandContent()}\" alt=\"{$node->commandContent()}\" />";
  }

  private static function renderCaptionEnvironment($node, string $body = null): string
  {

    if ($node->ancestorOfType('verbatim-environment')) return $node->commandSource();

    return match ($node->parent()->commandContent()) {

      'figure' => "<figcaption>$body</figcaption>",

      'table' => "<div class=\"table-caption\">$body</div>",

      default => "<div class=\"{$node->parent()->commandContent()}-caption\">$body</div>",

    };
  }

  private static function renderCitations($node, string $body = null) : string
  {

    $options = $node->commandOptions() != '' ? ", " . $node->commandOptions() : null;

    $ids = array_map(trim(...), explode(',', $node->commandContent()));

    $nums = explode(',', $node->body);

    foreach (array_combine($ids, $nums) as $id => $num) {
      $a[] = "<a href=\"#$id\">$num</a>";
    }

    $value = implode(', ', $a);

    return "[$value$options]";

  }
}
