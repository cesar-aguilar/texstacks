<?php

namespace TexStacks\Nodes;

use TexStacks\Nodes\CommandNode;

class SpacingCommandNode extends CommandNode
{

  public function render($body): string
  {
    if ($this->ancestorOfType(['environment:verbatim', 'environment:inlinemath', 'environment:displaymath', 'inlinemath', 'displaymath'])) return $this->commandSource() . ' ';

    return match ($this->commandName()) {
      'smallskip' => '<div style="height: 1em;"></div>',
      'medskip' => '<div style="height: 2em;"></div>',
      'bigskip' => '<div style="height: 3em;"></div>',
      default => '',
    };
  }

}