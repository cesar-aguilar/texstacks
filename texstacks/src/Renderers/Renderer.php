<?php

namespace TexStacks\Renderers;

use TexStacks\Renderers\EnvironmentRenderer;
use TexStacks\Renderers\SectionCommandRenderer;
use TexStacks\Renderers\ListEnvironmentRenderer;
use TexStacks\Renderers\AmsMathEnvironmentRenderer;

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

    if ($node->type() === 'section-cmd')
    {
      return SectionCommandRenderer::renderNode($node, $body);
    }
    else if ($node->type() === 'math-environment')
    {
      return AmsMathEnvironmentRenderer::renderNode($node, $body);
    }
    else if ($node->type() === 'environment')
    {
      return EnvironmentRenderer::renderNode($node, $body);
    }
    else if ($node->type() === 'tabular-environment')
    {
      return TabularEnvironmentRenderer::renderNode($node, $body);
    }
    else if ($node->type() === 'list-environment')
    {
      return ListEnvironmentRenderer::renderNode($node, $body);
    }
    else if ($node->type() === 'font-environment')
    {
      return FontEnvironmentRenderer::renderNode($node, $body);
    }
    else if ($node->type() === 'item')
    {
      return "<li>$body</li>";
    }
    else if ($node->type() === 'includegraphics')
    {
      return "<img src=\"{$node->commandContent()}\" alt=\"{$node->commandContent()}\" />";
    }
    else
    {
      if ($node->type() === 'label') return $node->commandSource();

      if ($node->ancestorOfType('math-environment')) return $body;

      if ($body == '' && $node->leftSibling()?->type() === 'text') return "<br><br>";

      if ($node->ancestorOfType('tabular-environment')) return $body;

      // Remove vertical spacing of the type \\[1em] if not in tabular environment
      $output = preg_replace('/(\\\)(\\\)\[(.*?)\]/', '', $body);
      return $output;
      // return str_replace('\\\\','', $output);

    }
  }
}
