<?php

namespace TexStacks\Nodes;

use TexStacks\Nodes\EnvironmentNode;
use TexStacks\Renderers\GroupEnvironmentRenderer;

class GroupNode extends EnvironmentNode
{

  public function render($body): string
  {
    return GroupEnvironmentRenderer::renderNode($this, $body);
  }

}