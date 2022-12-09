<?php

namespace TexStacks\Nodes;

use TexStacks\Renderers\SymbolCommandRenderer;
use TexStacks\Nodes\CommandNode;

class SymbolNode extends CommandNode
{

  public function render($body): string
  {
    return SymbolCommandRenderer::renderNode($this, $body);
  }

}