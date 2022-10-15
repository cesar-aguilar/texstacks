<?php

namespace TexStacks\Renderers;

use TexStacks\Renderers\EnvironmentRenderer;
use TexStacks\Renderers\SectionCommandRenderer;
use TexStacks\Renderers\FontCommandRenderer;
use TexStacks\Renderers\ListEnvironmentRenderer;
use TexStacks\Renderers\DisplayMathEnvironmentRenderer;
use TexStacks\Renderers\TabularEnvironmentRenderer;

class Renderer
{
  public function renderTree($root): string
  {
    return $this->renderRecursively($root);
  }

  private function renderRecursively($node): string
  {
    if ($node->isLeaf()) {
      return $this->renderNode($node, $node->body());
    }

    return $this->renderNode(
      $node,
      implode('', array_map(fn ($child) => $this->renderRecursively($child), $node->children()))
    );
  }

  private function renderNode($node, string $body = null): string
  {

    if ($node->type() === 'section-cmd') return SectionCommandRenderer::renderNode($node, $body);

    if ($node->type() === 'displaymath-environment') return DisplayMathEnvironmentRenderer::renderNode($node, $body);

    if ($node->type() === 'environment') return EnvironmentRenderer::renderNode($node, $body);

    if ($node->type() === 'tabular-environment') return TabularEnvironmentRenderer::renderNode($node, $body);

    if ($node->type() === 'list-environment') return ListEnvironmentRenderer::renderNode($node, $body);

    if ($node->type() === 'font-cmd') return FontCommandRenderer::renderNode($node, $body);

    if ($node->type() === 'item') return "<li>$body</li>";

    if ($node->type() === 'includegraphics') return self::renderIncludeGraphics($node, $body);

    if ($node->type() === 'caption') return self::renderCaptionEnvironment($node, $body);

    if ($node->type() === 'label') return $node->commandSource();

    if ($node->ancestorOfType(['displaymath-environment', 'tabular-environment'])) return $body;

    if ($body == '' && $node->leftSibling()?->type() === 'text') return "<br><br>";

    // Remove vertical spacing of the type \\[1em] since not in tabular-like environment
    $output = preg_replace('/(\\\)(\\\)\[(.*?)\]/', '', $body);

    // If parent is verbatim then add new line
    if ($node->parent()?->commandContent() === 'verbatim') $output = $output . "\n";

    // Remove double backslashes (the node is text and should not be in math or tabular environment)
    return preg_replace('/(\\\)(\\\)/', '', $output);

    // return str_replace('\\\\','', $output);

  }

  private static function renderIncludeGraphics($node, string $body = null): string
  {
    return "<img src=\"{$node->commandContent()}\" alt=\"{$node->commandContent()}\" />";
  }

  private static function renderCaptionEnvironment($node, string $body = null): string
  {
    return match ($node->parent()->commandContent()) {

      'figure' => "<figcaption>$body</figcaption>",

      'table' => "<div class=\"table-caption\">$body</div>",

      default => "<div class=\"{$node->parent()->commandContent()}-caption\">$body</div>",

    };
  }
}
