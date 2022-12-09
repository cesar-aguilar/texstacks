<?php

namespace TexStacks\Nodes;

use TexStacks\Nodes\CommandNode;

class IncludeGraphicsNode extends CommandNode
{

  public function render($body): string
  {
    if ($this->ancestorOfType('environment:verbatim')) return $this->commandSource();

    return "<img src=\"{$this->commandContent()}\" alt=\"{$this->commandContent()}\" />";
  }

}