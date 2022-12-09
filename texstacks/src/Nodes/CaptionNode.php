<?php

namespace TexStacks\Nodes;

use TexStacks\Renderers\CaptionRenderer;
use TexStacks\Nodes\CommandNode;

class CaptionNode extends CommandNode
{

  public function render($body): string
  {
    return CaptionRenderer::renderNode($this, $body);
  }

}