<?php

namespace TexStacks\Nodes;

use TexStacks\Nodes\CommandNode;

class FontDeclarationNode extends CommandNode
{

  public function render($body): string
  {
    if ($this->ancestorOfType('environment:verbatim')) return "\\" . $this->body;

    return '';

  }

}