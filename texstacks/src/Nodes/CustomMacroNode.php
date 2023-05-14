<?php

namespace TexStacks\Nodes;

use TexStacks\Renderers\CustomMacroRenderer;
use TexStacks\Nodes\CommandNode;

class CustomMacroNode extends CommandNode
{

  public function render($body): string
  {
    return CustomMacroRenderer::renderNode($this, $body);
  }

}