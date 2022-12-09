<?php

namespace TexStacks\Nodes;

use TexStacks\Renderers\ListEnvironmentRenderer;
use TexStacks\Nodes\EnvironmentNode;

class ListNode extends EnvironmentNode
{

  public function render($body): string
  {
    return ListEnvironmentRenderer::renderNode($this, $body);
  }

}