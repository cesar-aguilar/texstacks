<?php

namespace TexStacks\Nodes;

use TexStacks\Nodes\CommandNode;
use TexStacks\Renderers\Renderer;

class TwoArgsNode extends CommandNode
{

  public function render($body): string
  {
    if ($this->ancestorOfType(['environment:verbatim', 'environment:inlinemath', 'environment:displaymath', 'inlinemath', 'displaymath'])) return $this->commandSource();

    if ($this->commandName() === 'texorpdfstring') {
      return Renderer::render($this->commandContent());
    }

    return $this->commandSource();
  }

}