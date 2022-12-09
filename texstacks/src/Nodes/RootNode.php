<?php

namespace TexStacks\Nodes;

use TexStacks\Nodes\CommandNode;

class RootNode extends CommandNode
{

  public function render($body): string
  {
    $body = preg_replace('/^(<br>)+|(<br>)+$/', '', $body);

    if ($this->hasClasses()) {
      $classes = $this->getClasses();
      return "<span class='$classes'>$body</span>";
    }
    return $body;
  }

}