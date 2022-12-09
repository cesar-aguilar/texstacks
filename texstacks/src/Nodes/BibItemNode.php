<?php

namespace TexStacks\Nodes;

use TexStacks\Nodes\CommandNode;

class BibItemNode extends CommandNode
{

  public function render($body): string
  {
    if ($this->ancestorOfType('environment:verbatim')) return $this->commandSource() . ' ' . $body;

    $body = trim(str_replace(['<br>', '\newblock'], '', $body));

    return "<li id=\"{$this->commandContent()}\">$body</li>";
  }

}