<?php

namespace TexStacks\Nodes;

use TexStacks\Renderers\TabularEnvironmentRenderer;
use TexStacks\Nodes\EnvironmentNode;

class TabularNode extends EnvironmentNode
{

  public function render($body): string
  {
    return TabularEnvironmentRenderer::renderNode($this, $body);
  }

}