<?php

namespace TexStacks\Renderers;

class AmsMathEnvironmentRenderer
{

  public static function renderNode($node, $body = null)
  {
    return "\n{$node->commandSource()}\n$body\n\\end{" . $node->commandContent() . "}\n";
  }
}
