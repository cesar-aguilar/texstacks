<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\Node;

abstract class Renderer
{
  public function renderTree(Node $root): string
  {
    return $this->renderRecursively($root);
  }

  abstract public function renderNode(Node $node, string|null $body = null): string;

  /**
   * 
   */
  private function renderRecursively($node): string
  {
    if ($node->isLeaf()) {
      return $this->renderNode($node);
    }

    return $this->renderNode(
      $node,
      implode('', array_map(fn ($child) => $this->renderRecursively($child), $node->children()))
    );
  }
}
