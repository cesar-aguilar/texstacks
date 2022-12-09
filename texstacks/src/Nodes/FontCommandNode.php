<?php

namespace TexStacks\Nodes;

use TexStacks\Renderers\FontCommandRenderer;
use TexStacks\Nodes\CommandNode;

class FontCommandNode extends CommandNode
{

  public function render($body): string
  {
    return FontCommandRenderer::renderNode($this, $body);
  }

}