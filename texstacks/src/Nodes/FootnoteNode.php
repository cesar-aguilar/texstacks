<?php

namespace TexStacks\Nodes;

use TexStacks\Nodes\Node;
use TexStacks\Nodes\CommandNode;
use TexStacks\Renderers\Renderer;

class FootnoteNode extends CommandNode
{

  public function render($body): string
  {
    if ($this->ancestorOfType(['environment:displaymath', 'environment:inlinemath', 'displaymath', 'inlinemath', 'environment:verbatim'])) return $this->commandSource();

    $body = $this->commandContent();

    if ($this->commandContent() instanceof Node) {
      $body = trim(Renderer::render($this->commandContent()));
    }

    $num = chr((int)$this->commandRefNum() + 96);

    return "<details class=\"footnote\"><summary class=\"footnote\">[$num]</summary><p class=\"footnote-content\">$body</p></details>";

  }

}