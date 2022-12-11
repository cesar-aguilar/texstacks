<?php

namespace TexStacks\Nodes;

use TexStacks\Nodes\CommandNode;

class TagNode extends CommandNode
{

  public function render($body): string
  {
    return "\\tag{" . $body . "}";
  }
}
