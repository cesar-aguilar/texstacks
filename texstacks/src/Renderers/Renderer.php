<?php

namespace TexStacks\Renderers;

use TexStacks\Parsers\Node;

abstract class Renderer
{
  public function renderTree(Node $root): string
  {
    return $this->renderRecursively($root);
  }

  abstract protected function renderNode(Node $node, string $body = null): string;

  /**
   * 
   */
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
}
