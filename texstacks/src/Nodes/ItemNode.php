<?php

namespace TexStacks\Nodes;

use TexStacks\Nodes\CommandNode;

class ItemNode extends CommandNode
{

  public function render($body): string
  {
    if ($this->ancestorOfType('environment:verbatim')) return $this->commandSource() . ' ' . $body;

    if ($this->parent()?->commandContent() === 'description') {
      $label = $this->commandOptions();
      return "<dt>$label</dt><dd>$body</dd>";
    }

    $body = trim($body);

    return "<li>$body</li>";
  }

}