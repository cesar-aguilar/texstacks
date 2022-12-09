<?php

namespace TexStacks\Nodes;

use TexStacks\Nodes\EnvironmentNode;

class InlineMathNode extends EnvironmentNode
{

  public function render($body): string
  {
    return "\\(" . $body . "\\)";
  }

}