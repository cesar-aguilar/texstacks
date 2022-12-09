<?php

namespace TexStacks\Nodes;

use TexStacks\Nodes\CommandNode;

class LabelNode extends CommandNode
{

  public function render($body): string
  {
    return $this->commandSource();
  }

}