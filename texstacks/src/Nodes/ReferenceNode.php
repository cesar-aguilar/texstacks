<?php

namespace TexStacks\Nodes;

use TexStacks\Nodes\CommandNode;

class ReferenceNode extends CommandNode
{

  public function render($body): string
  {

    if ($this->commandName() === 'ref') {
      return "<a href='#{$this->commandContent()}'>{$this->commandOptions()}</a>";
    }

    return "(<a style=\"margin:0 0.1rem;\" href='#{$this->commandContent()}'>{$this->commandOptions()}</a>)";
  }

}