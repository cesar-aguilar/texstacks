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

    if ($node->type() === 'section-cmd') {
      return SectionCommandRenderer::renderNode($node, $body);
    }
    else if ($node->type() === 'math-environment') {
      return AmsMathEnvironmentRenderer::renderNode($node, $body);
    }
    else if ($node->type() === 'environment') {
      return EnvironmentRenderer::renderNode($node, $body);
    }
    else if ($node->type() === 'list-environment') {
      return ListEnvironmentRenderer::renderNode($node, $body);
    }
    else if ($node->type() === 'item') {
      return "<li>$body</li>";
    }
    else {
      return "$body";
    }
  }
}
