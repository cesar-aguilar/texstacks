<?php

namespace TexStacks\Nodes;

use TexStacks\Nodes\CommandNode;
use TexStacks\Renderers\Renderer;

class CitationNode extends CommandNode
{

  public function render($body): string
  {
    if ($this->commandOptions() instanceof Node) {
      $this->setOptions(Renderer::render($this->commandOptions()));
    }

    $options = $this->commandOptions() != '' ? ", " . $this->commandOptions() : null;

    $ids = array_map(trim(...), explode(',', $this->commandContent()));

    $nums = explode(',', $this->body);

    foreach (array_combine($ids, $nums) as $id => $num) {
      $a[] = "<a href=\"#$id\">$num</a>";
    }

    $value = implode(', ', $a);

    return " [$value$options]";
  }

}