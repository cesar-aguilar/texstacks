<?php

namespace TexStacks\Renderers;

use TexStacks\Nodes\Node;
use TexStacks\Nodes\CommandNode;

class CustomMacroRenderer
{

  public static function renderNode(CommandNode $node, string $body = null): string
  {
    if ($node->ancestorOfType('environment:verbatim')) return $node->commandSource();

    if ($node->commandContent() instanceof Node) {
      $body = Renderer::render($node->commandContent());
    }

    return $body;
  }
}
