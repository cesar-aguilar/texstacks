<?php

namespace TexStacks\Nodes;

use TexStacks\Nodes\CommandNode;
use TexStacks\Renderers\EnvironmentRenderer;

class EnvironmentNode extends CommandNode
{

  public function render($body): string
  {
    return EnvironmentRenderer::renderNode($this, $body);
  }

}
